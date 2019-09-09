<?php

namespace App\Http\Controllers\Api;

use App\ApiResponse;
use App\Book;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    protected $apiResponse;
    protected $cacheTime;

    public function __construct(ApiResponse $apiResponse)
    {
        $this->apiResponse = $apiResponse;
        $this->cacheTime = 600;
    }

    public function listBookPages(Request $request, $bookId)
    {
        $offset = $request->get('offset') ?? 0;
        $limit = $request->get('limit') ?? 20;

        $cacheKey = md5('listBookPages' . $bookId . $offset . $limit);

        if (Cache::tags(['pages'])->has($cacheKey)) {
            $cacheResp = Cache::tags(['pages'])->get($cacheKey);
            return $this->apiResponse->makeApiResponse(unserialize($cacheResp), 200);
        }

        try {
            $book = Book::with(['pages' => function ($q) use ($limit, $offset) {
                $q->skip($offset)
                    ->take($limit);
            }])->findOrFail($bookId);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponse->makeApiErrorResponse(404);
        }

        $responseBody["items"] = $book->pages->map(function ($bookPage) {
            return [
                "id" => $bookPage->id,
                "book_id" => $bookPage->book_id,
                "number" => $bookPage->number,
                "text" => $bookPage->text,
                "url" => "/books/" . $bookPage->book_id . "/pages/" . $bookPage->number
            ];
        });
        $responseBody["items_count"] = $book->book_pages;
        $responseBody["items_left"] = max($book->book_pages - ($limit + $offset), 0);

        Cache::tags(['pages'])->put($cacheKey, serialize($responseBody), $this->cacheTime);

        return $this->apiResponse->makeApiResponse($responseBody, 200);
    }

    public function getBookPage($bookId, $pageNumber)
    {
        try {
            $book = Book::with(['page' => function ($q) use ($pageNumber) {
                $q->where('number', $pageNumber);
            }])->findOrFail($bookId);
        } catch (ModelNotFoundException $e) {
            return $this->apiResponse->makeApiErrorResponse(404);
        }

        if (boolval($book->is_public) === false) {
            return $this->apiResponse->makeApiErrorResponse(404);
        } elseif ($book->page === null) {
            return $this->apiResponse->makeApiErrorResponse(404);
        } elseif (boolval($book->page->is_public) === false) {
            return $this->apiResponse->makeApiErrorResponse(404);
        }

        $responseBody = [
            "id" => $book->page->id,
            "book_id" => $book->id,
            "number" => $book->page->number,
            "text" => $book->page->text
        ];

        return $this->apiResponse->makeApiResponse($responseBody, 200);
    }
}
