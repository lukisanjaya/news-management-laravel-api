<?php

namespace App\Repositories;

use App\Tag;
use App\Http\Controllers\Api\ApiResponse;
use App\Http\Requests\TagRequest;
use App\Http\Resources\TagCollection;
use App\Http\Resources\TagResource;
use App\Interfaces\TagInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TagRepository implements TagInterface
{
    public function getAllTag(Request $request)
    {
        $perPage = $request->limit ?? 20;
        $perPage = $perPage > 50 ? 20 : $perPage;

        $keyRedis = 'category:collection:' . $perPage . ':' . json_encode($request->only(['q']));
        if (Cache::has($keyRedis)) {
            $respond  = json_decode(Cache::get($keyRedis));
            if (!empty($respond)) {
                return response()->json($respond, Response::HTTP_OK);
            }
        }

        $tag = Tag::orderBy('name', 'asc');

        if ($request->get('q')) {
            $tag = $tag->where('name', 'like', '%'. $request->get('q') . '%');
        }

        $tag = $tag->paginate($perPage);

        $respond  = new TagCollection($tag);
        if ($tag->count()) {
            Cache::put($keyRedis, json_encode($respond), now()->addMinutes(2));
        }

        return response()->json($respond, ($respond->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
    }

    public function getTagById($id)
    {
        $keyRedis = 'tag:item:' . $id;
        if (Cache::has($keyRedis)) {
            $respond = json_decode(Cache::get($keyRedis));
            if (!empty($respond)) {
                return response()->json($respond, Response::HTTP_OK);
            }
        }

        try {
            $tag = Tag::findOrFail($id);

            $respond  = new TagResource($tag);
            if ($tag->count()) {
                Cache::put($keyRedis, json_encode($respond), now()->addMinutes(2));
            }

            return response()->json($respond, ($tag->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
        } catch (\Throwable $th) {
            return ApiResponse::notFound();
        }
    }

    public function insertTag(TagRequest $request)
    {
        $name = $request->name;
        $slug = Str::slug($name);
        $tag = Tag::where('slug', $slug)->count();
        if ($tag) {
            return ApiResponse::badRequest('Duplicate Tag');
        }

        $tag = new Tag();
        $tag->name = $name;
        $tag->slug = $slug;
        $tag->save();

        $respond = new TagResource($tag);
        return response()->json($respond, Response::HTTP_OK);
    }

    public function updateTag(TagRequest $request, int $id)
    {
        try {
            $tag = Tag::findOrFail($id);
            $name           = $request->name;
            $slug           = Str::slug($name);
            $tag->name = $name;
            $tag->slug = $slug;
            $tag->save();

            $respond = new TagResource($tag);
            return response()->json($respond, Response::HTTP_OK);
        } catch (\Throwable $th) {
            return ApiResponse::badRequest('Tag Not Found');
        }
    }

    public function deleteTag(int $id)
    {
        try {
            $tag = Tag::findOrFail($id);
            $tag->delete();
            return ApiResponse::successDelete();
        } catch (\Throwable $th) {
            return ApiResponse::badRequest('Tag Not Found');
        }
    }
}
