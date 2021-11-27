<?php
namespace App\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorResponse extends JsonResponse
{
    public function __construct(string $code, string $message)
    {
        parent::__construct(
            ['code' => $code, 'message' => $message],
            Response::HTTP_BAD_REQUEST,
            ['Content-type' => 'application/json']);
    }
}