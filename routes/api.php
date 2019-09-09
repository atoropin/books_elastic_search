<?php
/*
 * Api version 2.0 (init. 01.08.2019)
 */
Route::prefix('v1')->group(function () {
    /**
     * Books
     * @params limit, offset, order
     */
    Route::get('/books', 'Api\BookController@listBooks');
    Route::get('/books/{bookId}', 'Api\BookController@getBook');
    /**
     * Book Pages
     * @params limit, offset
     */
    Route::get('/books/{bookId}/pages', 'Api\PageController@listBookPages');
    Route::get('/books/{bookId}/pages/{pageNumber}', 'Api\PageController@getBookPage');
    /**
     * Search
     * @params limit, offset
     */
    Route::post('/search/books', 'Api\BookController@searchBooks');
    Route::post('/search/books/{bookId}', 'Api\PageController@searchBookPages');
    /**
     * Search filters
     */
    Route::get('/search/books_filters', 'Api\BookController@searchBooksFilters');
});
