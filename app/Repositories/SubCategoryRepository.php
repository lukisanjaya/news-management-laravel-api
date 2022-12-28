<?php

namespace App\Repositories;

use App\SubCategory;
use App\Http\Controllers\Api\ApiResponse;
use App\Http\Requests\SubCategoryRequest;
use App\Http\Resources\SubCategoryCollection;
use App\Http\Resources\SubCategoryResource;
use App\Interfaces\SubCategoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SubCategoryRepository implements SubCategoryInterface
{
    public function getAllSubCategory(Request $request)
    {
        $perPage     = $request->limit ?? 20;
        $perPage     = $perPage > 50 ? 20 : $perPage;

        $keyRedis = 'subcategory:collection:' . $perPage . ':' . json_encode($request->only(['q', 'slug', 'category_id', 'category_slug', 'tag_id', 'tag_slug', 'subcategory_id', 'subcategory_slug']));
        if (Cache::has($keyRedis)) {
            $respond  = json_decode(Cache::get($keyRedis));
            if (!empty($respond)) {
                return response()->json($respond, Response::HTTP_OK);
            }
        }
        $subCategory = SubCategory::with('category')->orderBy('name', 'asc');

        if ($request->get('q')) {
            $subCategory = $subCategory->where('sc.name', 'like', '%'. $request->get('q') . '%');
        }

        $subCategory = $subCategory->paginate($perPage);
        $respond     = new SubCategoryCollection($subCategory);

        if ($subCategory->count()) {
            Cache::put($keyRedis, json_encode($respond), now()->addMinutes(2));
        }
        return response()->json($respond, ($respond->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
    }

    public function getSubCategoryById($id)
    {
        $keyRedis = 'subcategory:item:' . $id;
        if (Cache::has($keyRedis)) {
            $respond = json_decode(Cache::get($keyRedis));
            if (!empty($respond)) {
                return response()->json($respond, Response::HTTP_OK);
            }
        }

        try {
            $subCategory = SubCategory::findOrFail($id);

            $respond  = new SubCategoryResource($subCategory);
            if ($subCategory->count()) {
                Cache::put($keyRedis, json_encode($respond), now()->addMinutes(2));
            }
            return response()->json($respond, ($subCategory->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
        } catch (\Throwable $th) {
            return ApiResponse::notFound();
        }
    }

    public function insertSubCategory(SubCategoryRequest $request)
    {
        $name = $request->name;
        $slug = Str::slug($name);
        $subCategory = SubCategory::where('slug', $slug)->count();
        if ($subCategory) {
            return ApiResponse::badRequest('Duplicate Category');
        }

        $subCategory              = new SubCategory();
        $subCategory->name        = $name;
        $subCategory->category_id = $request->category_id;
        $subCategory->slug        = $slug;
        $subCategory->save();

        $respond = new SubCategoryResource($subCategory);
        return response()->json($respond, Response::HTTP_OK);
    }

    public function updateSubCategory(SubCategoryRequest $request, int $id)
    {
        try {
            $subCategory = SubCategory::findOrFail($id);
            $name           = $request->name;
            $slug           = Str::slug($name);
            $subCategory->name = $name;
            $subCategory->slug = $slug;
            $subCategory->save();

            $respond = new SubCategoryResource($subCategory);
            return response()->json($respond, Response::HTTP_OK);
        } catch (\Throwable $th) {
            return ApiResponse::badRequest('Category Not Found');
        }
    }

    public function deleteSubCategory(int $id)
    {
        try {
            $subCategory = SubCategory::findOrFail($id);
            $subCategory->delete();
            return ApiResponse::successDelete();
        } catch (\Throwable $th) {
            return ApiResponse::badRequest('Category Not Found');
        }
    }
}
