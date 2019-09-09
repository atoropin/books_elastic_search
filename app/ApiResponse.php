<?php

namespace App;

class ApiResponse
{
    public function makeApiResponse($responseBody = null, $status)
    {
        return response()
            ->json([
                'response' => $responseBody
            ], $status)
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
    }

    /**
     * List of returning errors for api 404 exception
     *
     * @var array
     */
    protected $errors = [
        "user_message" => "Sorry, this page does not exist",
        "internal_message" => "The requested resource does not exist"
    ];

    public function makeApiErrorResponse($status)
    {
        $responseBody = null;
        $errors = $this->errors;

        return response()
            ->json([
                'response' => $responseBody, 'errors' => $errors
            ], $status)
            ->withHeaders([
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
    }
}
