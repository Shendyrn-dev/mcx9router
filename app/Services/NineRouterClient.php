<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NineRouterClient
{
    /** Key cache untuk token auth 9Router. */
    public const TOKEN_KEY = 'ninerouter:token';

    /** Buang token cache supaya request berikutnya login ulang. */
    public function forgetToken(): void
    {
        Cache::forget(self::TOKEN_KEY);
    }

    private function cfg(string $key): ?string
    {
        return config("services.ninerouter.$key");
    }

    /** Login ke 9Router, ambil cookie auth_token, simpan di cache 23 jam (token asli berlaku 24 jam). */
    private function login(): string
    {
        $res = Http::acceptJson()->asJson()
            ->withUserAgent('mcx9route/1.0')
            ->post(rtrim($this->cfg('url'), '/') . $this->cfg('login_path'), [
                $this->cfg('login_field') => $this->cfg('password'),
            ]);

        if (!$res->successful()) {
            throw new RuntimeException("Login ke 9Router gagal (status {$res->status()}). Periksa NINE_ROUTER_PASSWORD dan NINE_ROUTER_LOGIN_FIELD.");
        }

        foreach ($res->headers() as $name => $values) {
            if (strtolower($name) !== 'set-cookie') continue;
            foreach ((array) $values as $line) {
                if (preg_match('/auth_token=([^;]+)/', $line, $m)) {
                    Cache::put(self::TOKEN_KEY, $m[1], now()->addHours(23));
                    return $m[1];
                }
            }
        }
        throw new RuntimeException('Login berhasil, tetapi cookie auth_token tidak ditemukan di respons.');
    }

    private function request(string $path, string $period, string $token): Response
    {
        return Http::acceptJson()
            ->withUserAgent('mcx9route/1.0')
            ->withHeaders(['Cookie' => 'auth_token=' . $token])
            ->get(rtrim($this->cfg('url'), '/') . $path, ['period' => $period]);
    }

    /** Kirim request terautentikasi untuk membangunkan instance, dengan timeout cold start. */
    public function wakeUp(): Response
    {
        $baseUrl = rtrim((string) $this->cfg('url'), '/');
        $path = (string) $this->cfg('stats_path');

        // 1. Ping base URL – triggers cold start without auth.
        try {
            Http::withUserAgent('mcx9route/1.0')
                ->connectTimeout(10)
                ->timeout(45)
                ->get($baseUrl . '/');
        } catch (\Throwable $e) {
            // ignore timeout – instance may still be booting
        }

        // 2. Obtain (or refresh) auth token, retry login few times.
        $token = null;
        for ($i = 0; $i < 3; $i++) {
            try {
                $token = $this->login();
                break;
            } catch (\Throwable $e) {
                if ($i === 2) {
                    throw $e;
                }
                // wait a few seconds before next attempt
                sleep(3);
            }
        }

        // 3. Request stats (any endpoint) just to confirm instance up.
        $request = fn (string $authToken): Response => Http::acceptJson()
            ->withUserAgent('mcx9route/1.0')
            ->withHeaders(['Cookie' => 'auth_token=' . $authToken])
            ->connectTimeout(15)
            ->timeout(45)
            ->get($baseUrl . $path, ['period' => 'today']);

        $res = $request($token);
        if ($res->status() === 401) {
            $this->forgetToken();
            $res = $request($this->login());
        }
        if (!$res->successful()) {
            throw new RuntimeException("9Router membalas {$res->status()} saat wake-up");
        }

        return $res;
    }

    /** Ambil data dari 9Router; kalau token ditolak (401), login ulang sekali lalu coba lagi. */
    public function get(string $path, string $period): array
    {
        $res = $this->request($path, $period, Cache::get(self::TOKEN_KEY) ?? $this->login());

        if ($res->status() === 401) {
            Cache::forget(self::TOKEN_KEY);
            $res = $this->request($path, $period, $this->login());
        }
        if (!$res->successful()) {
            throw new RuntimeException("9Router membalas {$res->status()} untuk $path");
        }
        return $res->json() ?? [];
    }
}
