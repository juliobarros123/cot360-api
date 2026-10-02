<?php



namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Str;

class SsoService
{
    public function getUserData($token)
    {
        $cacheKey = "user_data_{$token}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $lastResponse = null;
// dd($this->candidateBaseUrls());
            foreach ($this->candidateBaseUrls() as $baseUrl) {
                $response = Http::withToken($token)
                    ->timeout(3)
                    ->acceptJson()
                    ->get(rtrim($baseUrl, '/') . '/api/oauth/user');

                if ($response->successful()) {
                    $data = $response->json();
                    if (!$data || !isset($data['user'])) {
                        return null;
                    }

                    Cache::put($cacheKey, $data, now()->addMinutes(30));
                    return $data;
                }

                $lastResponse = $response;
            }

            if ($lastResponse) {
                Log::warning('SSO respondeu token inválido/não autorizado', [
                    'status' => $lastResponse->status(),
                    'body' => $lastResponse->body(),
                    'token_prefix' => substr($token, 0, 12),
                    'sso_urls_testadas' => $this->candidateBaseUrls(),
                ]);
                return null;
            }
        } catch (Throwable $e) {
            dd($e);
            Log::warning('Falha ao validar token no SSO', [
                'message' => $e->getMessage(),
                'sso_urls_testadas' => $this->candidateBaseUrls(),
            ]);
            return null;
        }

        return null;
    }

  private function candidateBaseUrls(): array
{
    $configured = rtrim((string) config('services.sso.url'), '/');
    
    // Em ambiente Docker, tenta os nomes dos containers
    if (app()->environment('local')) {
        return [
            $configured,
            'http://omni-insurance-api-entity-nginx-1',
            'http://omni-insurance-api-entity-app-1:9000',
            'http://host.docker.internal:3001',
        ];
    }
    
    return [$configured];
}
}
