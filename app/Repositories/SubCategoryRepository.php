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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubCategoryRepository implements SubCategoryInterface
{
    public function getAllSubCategory(Request $request)
    {
        $perPage  = $request->limit ?? 20;
        $perPage  = $perPage > 50 ? 20 : $perPage;
        $subCategory = DB::table('subcategories as sc')
                        ->join('categories as c', 'sc.category_id', '=', 'c.id')
                        ->select(['sc.id', 'sc.name', 'sc.slug','c.id as category_id', 'c.name as category_name'])
                        ->orderBy('sc.name', 'asc');

        if ($request->get('q')) {
            $subCategory = $subCategory->where('sc.name', 'like', '%'. $request->get('q') . '%');
        }

        $subCategory = $subCategory->paginate($perPage);
        // dd($subCategory);
        $respond  = new SubCategoryCollection($subCategory);

        return response()->json($respond, ($respond->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
    }

    public function getSubCategoryById($id)
    {
        try {
            $subCategory = SubCategory::findOrFail($id);

            $respond  = new SubCategoryResource($subCategory);

            return response()->json($respond, ($respond->count() ? Response::HTTP_OK : Response::HTTP_NOT_FOUND));
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
