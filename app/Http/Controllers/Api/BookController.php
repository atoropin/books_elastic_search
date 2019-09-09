<?php

namespace App\Http\Controllers\Api;

use App\ApiResponse;
use App\Author;
use App\Book;
use App\Http\Controllers\Controller;
use App\Page;
use App\BookSearchRule;
use App\PageSearchRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    protected $apiResponse;
    protected $cacheTime;

    protected $author;
    protected $book;

    public function __construct(ApiResponse $apiResponse, Author $author, Book $book)
    {
        $this->apiResponse = $apiResponse;
        $this->cacheTime = 60;

        $this->author = $author;
        $this->book = $book;
    }

    public function listBooks(Request $request)
    {
        $offset = $request->get('offset') ?? 0;
        $limit = $request->get('limit') ?? 20;
        $order = $request->get('order');

        $cacheKey = md5('listBooks' . $offset . $limit . $order);

        if (Cache::tags(['books'])->has($cacheKey)) {
            $cacheResp = Cache::tags(['books'])->get($cacheKey);
            return $this->apiResponse->makeApiResponse(unserialize($cacheResp), 200);
        }

        $books = Book::where('is_public', 1)
            ->with('author')
            ->when($order === null, function ($q) {
                $q->orderBy('pub_date', 'desc');
            })
            ->when($order !== null, function ($q) use ($order) {
                $orderField = explode(',', $order)[0];
                $orderType = explode(',', $order)[1] ?? 'asc';
                $q->orderBy($orderField, $orderType);
            })
            ->skip($offset)
            ->take($limit)
            ->get();

        $booksCount = Book::where('is_public', 1)->count();

        $responseBody["items"] = $books->map(function ($book) {
            return [
                "type" => "book",
                "id" => $book->id,
                "title" => $book->title,
                "author" => [
                    "id" => $book->author ? $book->author->id : null,
                    "name" => $book->author ? $book->author->name : null,
                ],
                "pub_date" => $book->pub_date
            ];
        });
        $responseBody["items_count"] = $booksCount;
        $responseBody["items_left"] = max($booksCount - ($limit + $offset), 0);

        Cache::tags(['books'])->put($cacheKey, serialize($responseBody), $this->cacheTime);

        return $this->apiResponse->makeApiResponse($responseBody, 200);
    }

    public function getBook($bookId)
    {
        $cacheKey = md5('getBook' . $bookId);

        if (Cache::tags(['books'])->has($cacheKey)) {
            $cacheResp = Cache::tags(['books'])->get($cacheKey);
            return $this->apiResponse->makeApiResponse(unserialize($cacheResp), 200);
        }

        try {
            $book = Book::with('author')
                ->findOrFail($bookId);

            if (boolval($book->is_public) === false) {
                return $this->apiResponse->makeApiErrorResponse(404);
            }
        } catch (ModelNotFoundException $e) {
            return $this->apiResponse->makeApiErrorResponse(404);
        }

        $responseBody = [
            "type" => "book",
            "id" => $book->id,
            "title" => $book->title,
            "author" => [
                "id" => $book->author ? $book->author->id : null,
                "name" => $book->author ? $book->author->name : null,
            ],
            "pub_date" => $book->pub_date
        ];

        Cache::tags(['books'])->put($cacheKey, serialize($responseBody), $this->cacheTime);

        return $this->apiResponse->makeApiResponse($responseBody, 200);
    }

    public function searchBooks(Request $request)
    {
        $searchQuery = $request->get('query');
        $searchFilters = $request->get('filter');
        $offset = $request->get('offset') ?? 0;
        $limit = $request->get('limit') ?? 20;
        $order = $request->get('order');

        $maxItems = 9999 - (9999 % $limit);

        $searchBook = $searchQuery ?
            Book::search($searchQuery)->rule(BookSearchRule::class) :
            Book::search('*')->where('is_active', 1);

        if (!empty($searchFilters)) {
            $this->filterSearchQuery($searchFilters, $searchBook);
        }

        if ($order !== null) {
            switch ($order['type']) {
                case 'relevant':
                    break;
                default:
                    $searchBook = $searchBook->orderBy($order['type'], $order['value']);
            }
        }

        /**
         * Custom pagination with from/take Elastic offset
         */
        $booksCount = $searchBook->raw();
        $booksCount = $booksCount['hits']['total'];

        $books = $searchBook
            ->from($offset)
            ->take($limit)
            ->raw();

        $books = collect($books["hits"]["hits"]);

        $responseBody["items"] = $books->isNotEmpty() ? $books->map(function($book) {
            return [
                "type" => "book",
                "id" => $book["_source"]["id"],
                "title" => $book["_source"]["title"],
                "author" => [
                    "id" => $book["_source"]["author_id"],
                    "name" => $book["_source"]["author_name"],
                ],
                "pub_date" => $book["_source"]["pub_date"],
                "number" => null,
                "text" => null,
                "url" => "/books/" . $book["_source"]["id"]
            ];
        }) : null;
        $responseBody["search_filters"] = $searchFilters;

        /**
         * For empty query we don't need to search in books pages
         */
        $searchPage = $searchQuery ?
            Page::search($searchQuery)->rule(PageSearchRule::class) :
            null;

        if ($searchPage !== null) {

            if (!empty($searchFilters)) {
                $this->filterSearchQuery($searchFilters, $searchPage);
            }

            if ($order) {
                switch ($order['type']) {
                    case 'relevant':
                        break;
                    default:
                        $searchPage = $searchPage->orderBy($order['type'], $order['value']);
                }
            }

            $pagesCount = $searchPage->raw();
            $pagesCount = $pagesCount['hits']['total'];

            $totalItemsCount = ($booksCount + $pagesCount) <= $maxItems ? ($booksCount + $pagesCount) : $maxItems;

            $responseBody["items_count"] = $totalItemsCount;

            /**
             * Checking if books for page remains less than per page needed
             */
            if ($booksCount < ($offset + $limit)) {
                $pagesOffset = ($offset + $limit) - $booksCount;

                if ($pagesOffset > 0 && $pagesOffset < $limit) {
                    $pages = $searchPage->from(0)->take($pagesOffset)->raw();
                } else {
                    $pages = $searchPage->from($pagesOffset - $limit)->take($limit)->raw();
                }

                $pages = collect($pages["hits"]["hits"]);

                $responseBody["items"] = array_merge($responseBody["items"] ? $responseBody["items"]->toArray() : [], $pages->map(function ($page) {
                    return [
                        "type" => "page",
                        "id" => $page["_source"]["id"],
                        "book_id" => $page["_source"]["book_id"],
                        "title" => $page["_source"]["title"],
                        "author" => [
                            "id" => $page["_source"]["author_id"],
                            "name" => $page["_source"]["author_name"],
                        ],
                        "pub_date" => $page["_source"]['pub_date'],
                        "number" => $page["_source"]["number"],
                        "text" => $page["_source"]["text"],
                        "url" => "/books/" . $page["_source"]["book_id"] . "/pages/" . $page["_source"]["number"]
                    ];
                })->toArray());
                $responseBody["items_left"] = max($totalItemsCount - ($booksCount + $pagesOffset), 0);

            } else {
                $responseBody["items_left"] = max($totalItemsCount - ($limit + $offset), 0);
            }

        } else {
            $booksCount = $booksCount <= $maxItems ? $booksCount : $maxItems;
            $responseBody["items_count"] = $booksCount;
            $responseBody["items_left"] = max($booksCount - ($limit + $offset), 0);
        }

        return $this->apiResponse->makeApiResponse($responseBody, 200);
    }

    private function filterSearchQuery($searchFilters, $collection)
    {
        foreach ($searchFilters as $searchFilter) {
            switch ($searchFilter['field']) {
                case 'authors':
                    $collection = $collection->whereIn('author_id', $searchFilter['value']);
                    break;
                case 'pub_dates':
                    $collection = $collection->where('pub_date', '>=', $searchFilter['value'][0])
                        ->where('pub_date', '<=', $searchFilter['value'][1]);
                    break;
                default:
                    $collection = $collection->whereIn($searchFilter['field'], $searchFilter['value']);
            }
        }

        return $collection;
    }

    public function searchBooksFilters(Request $request)
    {
        $filterType = $request->get('type');
        $filterQuery = $request->get('query') ?? null;

        $cacheKey = md5('searchBooksFilters' . $filterType . $filterQuery);

        if (Cache::tags(['books'])->has($cacheKey)) {
            $cacheResp = Cache::tags(['books'])->get($cacheKey);
            return $this->apiResponse->makeApiResponse(unserialize($cacheResp), 200);
        }

        $filterList = [];
        switch ($filterType) {
            case 'authors':
                $filterList = $this->author->getAuthorsArrayByFirstLetter($filterQuery);
                break;
            case 'pub_dates':
                $filterList = $this->book->getExtremeDatesArray();
                break;
        }

        $responseBody = [];
        foreach ($filterList as $item) {
            $responseBody[] = [
                $item["id"] => $item["name"]
            ];
        }

        Cache::tags(['books'])->put($cacheKey, serialize($responseBody), 1440);

        return $this->apiResponse->makeApiResponse($responseBody, 200);
    }
}
