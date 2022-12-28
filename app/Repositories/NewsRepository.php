<?php

namespace App\Repositories;

use App\Helpers\ImageHelper;
use App\News;
use App\Http\Controllers\Api\ApiResponse;
use App\Http\Requests\NewsRequest;
use App\Http\Resources\NewsCollection;
use App\Http\Resources\NewsResource;
use App\Interfaces\NewsInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class NewsRepository implements NewsInterface
{
    public function getAllNews(Request $request)
    {
        $perPage = $request->limit ?? 20;
        $perPage = $perPage > 50 ? 20 : $perPage;

        $keyRedis = 'news:collection:' . $perPage . ':' . json_encode($request->only(['q', 'slug', 'category_id', 'category_slug', 'tag_id', 'tag_slug', 'subcategory_id', 'subcategory_slug']));
        if (Cache::has($keyRedis)) {
            $respond  = json_decode(Cache::get($keyRedis));
            if (!empty($respond)) {
                return response()->json($respond, Response::HTTP_OK);
            }
        }

        $news = News::with(
            [
                'user' => function ($query) {
                    $query->select(['id', 'username', 'name', 'avatar']);
                },
                'category',
                'subcategory' => function ($query) {
                    $query->select(['id', 'name', 'slug']);
                }
            ]
        )->orderBy('published_at', 'desc');

        if ($request->get('q')) {
            $news = $news->where('title', 'like', '%' . $request->get('q') . '%');
        }

        if ($request->get('slug')) {
            $news = $news->where('slug', $request->get('slug'));
        }

        if ($request->get('category_id')) {
            $news = $news->where('category_id', $request->get('category_id'));
        }

        if ($request->get('user_id')) {
            $news = $news->where('user_id', $request->get('user_id'));
        }

        if ($request->get('category_slug')) {
            $news = $news->where('category_slug', $request->get('category_slug'));
        }

        if ($request->get('tag_id')) {
            $news = $news->where('tag_id', $request->get('tag_id'));
        }

        if ($request->get('tag_slug')) {
            $news = $news->where('tag_slug', $request->get('tag_slug'));
        }

        if ($request->get('subcategory_slug')) {
            $news = $news->where('subcategory_slug', $request->get('subcategory_slug'));
        }

        if ($request->get('subcategory_id')) {
            $news = $news->where('subcategory_id', $request->get('subcategory_id'));
        }

        $news = $news->paginate($perPage);

        $respond  = new NewsCollection($news);

        if ($news->count()) {
            Cache::put($keyRedis, json_encode($respond), now()->addMinutes(2));
        }

        return response()->json($respond, ($news->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
    }

    public function getNewsById($id)
    {
        $keyRedis = 'news:item:' . $id;
        if (Cache::has($keyRedis)) {
            $respond = json_decode(Cache::get($keyRedis));
            if (!empty($respond)) {
                return response()->json($respond, Response::HTTP_OK);
            }
        }

        try {
            $news = News::where('id', $id)->with(
                [
                    'user' => function ($query) {
                        $query->select(['id', 'username', 'name', 'avatar']);
                    },
                    'tag' => function ($query)
                    {
                        $query->select(['tags.id', 'tags.name', 'tags.slug']);
                    },
                    'category',
                    'subcategory' => function ($query) {
                        $query->select(['id', 'name', 'slug']);
                    },

                ]
            )->first();
             $respond  = new NewsResource($news);
            if ($news->count()) {
                Cache::put($keyRedis, json_encode($respond), now()->addMinutes(2));
            }
            return response()->json($respond, ($news->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
        } catch (\Throwable $th) {
            return ApiResponse::notFound();
        }
    }

    public function insertNews(NewsRequest $request)
    {
        $title = $request->title;
        $slug = Str::slug($title);
        $news = News::where('slug', $slug)->count();
        if ($news) {
            return ApiResponse::badRequest('Duplicate News');
        }

        $news        = new News();
        $requestData = $request->all();

        $requestData['slug']      = $request->get('title');
        $requestData['author_id'] = Auth::guard()->user()->id;
        if ($request->file('image')) {
            $requestData['image'] = ImageHelper::uploadFile($request, $request->get('title'), 'articles');
        }

        $news->fill($requestData);
        $news->save();

        $news->tag()->attach(
            $request->get('tags')
        );

        $respond = new NewsResource($news);
        return response()->json($respond, Response::HTTP_OK);
    }

    public function updateNews(Request $request, int $id)
    {
        try {
            $news        = News::findOrFail($id);
            $requestData = $request->all();

            $requestData['slug']      = $request->get('title');
            $requestData['author_id'] = Auth::guard()->user()->id;
            if ($request->file('image')) {
                $requestData['image'] = ImageHelper::uploadFile($request, $request->get('title'), 'articles');
            }

            $news->fill($requestData);
            $news->save();

            $respond = new NewsResource($news);
            return response()->json($respond, Response::HTTP_OK);
        } catch (\Throwable $th) {
            return ApiResponse::badRequest('News Not Found');
        }
    }

    public function deleteNews(int $id)
    {
        try {
            $news = News::findOrFail($id);
            $news->delete();
            return ApiResponse::successDelete();
        } catch (\Throwable $th) {
            return ApiResponse::badRequest('News Not Found');
        }
    }
}
