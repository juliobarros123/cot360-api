<?php
// ============================================================
// ServiceBase.php — App\Services\ServiceBase
// ============================================================
namespace App\Services;

abstract class ServiceBase {
    protected function successResponse(mixed $data, string $message = 'OK', int $code = 200): array {
        return ['success' => true, 'message' => $message, 'data' => $data, 'code' => $code];
    }
    protected function errorResponse(string $message, int $code = 400, mixed $errors = null): array {
        return ['success' => false, 'message' => $message, 'errors' => $errors, 'code' => $code];
    }
    protected function generateSlug(string $text): string {
        return \Illuminate\Support\Str::slug($text . '-' . uniqid());
    }
}
