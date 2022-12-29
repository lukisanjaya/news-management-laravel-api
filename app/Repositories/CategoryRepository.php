<?php

namespace App\Repositories;

use App\Category;
use App\Http\Controllers\Api\ApiResponse;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Interfaces\CategoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryRepository implements CategoryInterface
{
    public function getAllCategory(Request $request)
    {
        $perPage  = $request->limit ?? 20;
        $perPage  = $perPage > 50 ? 20 : $perPage;

        $keyRedis = 'category:collection:' . $perPage . ':' . json_encode($request->only(['q']));
        if (Cache::has($keyRedis)) {
            $respond  = json_decode(Cache::get($keyRedis));
            if (!empty($respond)) {
                return response()->json($respond, Response::HTTP_OK);
            }
        }
        $category = Category::orderBy('name', 'asc');

        if ($request->get('q')) {
            $category = $category->where('name', 'like', '%'. $request->get('q') . '%');
        }

        $category = $category->paginate($perPage);

        $respond  = new CategoryCollection($category);

        if ($category->count()) {
            Cache::put($keyRedis, json_encode($respond), now()->addMinutes(2));
        }
        return response()->json($respond, ($respond->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
    }

    public function getCategoryById($id)
    {
        $keyRedis = 'category:item:' . $id;
        if (Cache::has($keyRedis)) {
            $respond = json_decode(Cache::get($keyRedis));
            if (!empty($respond)) {
                return response()->json($respond, Response::HTTP_OK);
            }
        }

        try {
            $category = Category::findOrFail($id);

            $respond  = new CategoryResource($category);
            if ($category->count()) {
                Cache::put($keyRedis, json_encode($respond), now()->addMinutes(2));
            }
            return response()->json($category->news, ($category->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
        } catch (\Throwable $th) {
            return ApiResponse::notFound();
        }
    }

    public function insertCategory(CategoryRequest $request)
    {
        $name = $request->name;
        $slug = Str::slug($name);
        $category = Category::where('slug', $slug)->count();
        if ($category) {
            return ApiResponse::badRequest('Duplicate Category');
        }

        $category = new Category();
        $category->name = $name;
        $category->slug = $slug;
        $category->save();

        $respond = new CategoryResource($category);
        return response()->json($respond, Response::HTTP_OK);
    }

    public function updateCategory(CategoryRequest $request, int $id)
    {
        try {
            $category = Category::findOrFail($id);
            $name           = $request->name;
            $slug           = Str::slug($name);
            $category->name = $name;
            $category->slug = $slug;
            $category->save();

            Cache::forget('category:item:' . $id);

            $respond = new CategoryResource($category);
            return response()->json($respond, Response::HTTP_OK);
        } catch (\Throwable $th) {
            return ApiResponse::badRequest('Category Not Found');
        }
    }

    public function deleteCategory(int $id)
    {
        try {
            Category::destroy($id);
            Cache::forget('category:item:' . $id);

            return ApiResponse::successDelete();
        } catch (\Throwable $th) {
            return ApiResponse::badRequest('Category Not Found');
        }
    }
}
