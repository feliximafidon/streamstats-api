<?php

namespace App\Traits;

use Illuminate\Encryption\Encrypter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    protected function jsonSuccess(array $data = [], string $message = null, int $http_status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        return $this->jsonSend(true, $message, $data, $http_status, $headers);
    }

    protected function jsonError(string $message = null, array $data = [], int $http_status = Response::HTTP_EXPECTATION_FAILED, array $headers = []): JsonResponse
    {
        return $this->jsonSend(false, $message, $data, $http_status, $headers);
    }

    protected function jsonNotFound(string $redirect_to = null, array $headers = []): JsonResponse
    {
        return $this->jsonSend(false, http_status: Response::HTTP_NOT_FOUND, redirect_to: $redirect_to);
    }

    protected function jsonUnauthorized(string $message, array $headers = []): JsonResponse
    {
        return $this->jsonSend(false, $message, null, headers: $headers);
    }

    protected function encryptToken(string $value, string $key = null, string $cipher = null): string
    {
        if (! $key) {
            return Crypt::encryptString($value);
        } else {
            return (new Encrypter($key, $cipher ?: config('app.cipher')))->encryptString($value);
        }
    }

    private function jsonSend(bool $status = true, string $message = null, array $data = null, int $http_status = Response::HTTP_OK, array $headers = [], string $redirect_to = null): JsonResponse
    {
        return new JsonResponse([
            'status' => $status,
            'data' => $data,
            'message' => $message,
            'redirect' => $redirect_to,
        ], $http_status, $headers);
    }
}