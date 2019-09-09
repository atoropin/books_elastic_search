<?php

Route::fallback(function(){
    $apiResponse = new \App\ApiResponse();
    return $apiResponse->makeApiErrorResponse(404);
});
