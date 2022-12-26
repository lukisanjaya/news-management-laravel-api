<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Response;
use stdClass;

class ApiResponse
{
    public static function notFound($message = 'Data Not Found', $isArray = false)
    {
        return response()->json(
            [
                'status'  => false,
                'message' => $message,
                'data'    => $isArray ? [] : new stdClass()
            ],
            Response::HTTP_NOT_FOUND
        );
    }

    public static function badRequest($message)
    {
        return response()->json(
            [
                'status'  => false,
                'message' => $message,
                'data'    => new stdClass()
            ],
            Response::HTTP_BAD_REQUEST
        );
    }

    public static function successItem($data, $message = 'Successfully Get Data')
    {
        $respond = new stdClass();
        if($data) :
            $statusCode       = Response::HTTP_OK;
            $respond->status  = true;
            $respond->message = $message;
            $respond->data    = $data;
            return response()->json($respond, $statusCode);
        else :
            return ApiResponse::notFound('Data Not Found', false);
        endif;

    }

    public static function successCollection($data, $message = 'Successfully Get Data')
    {
        $respond = new stdClass();
        if($data->count()) :
            $respond->status  = true;
            $respond->message = $message;
            $respond->data    = $data->getCollection();
            $respond->meta    = [
                // "first_page_url" => $data->onFirstPage(),
                // "last_page_url" => $data->onLastPage(),

                "current_page"  => $data->currentPage(),
                "last_page"     => $data->lastPage(),
                "prev_page_url" => $data->previousPageUrl(),
                "next_page_url" => $data->nextPageUrl(),
                "per_page"      => $data->perPage(),
                "from"          => $data->firstItem(),
                "to"            => $data->lastItem(),
                "total"         => $data->total(),
                "path"          => url()
            ];
            return response()->json($respond, Response::HTTP_OK);
        else :
            return ApiResponse::notFound('Data Not Found', true);
        endif;

    }

    public static function successCreate($data, $message = 'Successfully create data')
    {
        return response()->json(
            [
                'status'  => true,
                'message' => $message,
                'data'    => $data
            ],
            Response::HTTP_CREATED
        );
    }

    public static function successUpdate($data, $message = 'Successfully update data')
    {
        return response()->json(
            [
                'status'  => true,
                'message' => $message,
                'data'    => $data
            ],
            Response::HTTP_OK
        );
    }

    public static function successDelete($message = 'Successfully delete data')
    {
        return response()->json(
            [
                'status'  => true,
                'message' => $message,
                'data'    => new stdClass()
            ],
            Response::HTTP_OK
        );
    }
}
