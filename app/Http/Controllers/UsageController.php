<?php

namespace App\Http\Controllers;

use App\Services\NineRouterClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class UsageController extends Controller
{
    private const CACHE_DURATION = 3; // 3 detik, polling 5 detik (PRD 9.4)
    private const VALID_PERIODS = ['today', '24h', '7d', '30d', '60d', 'all'];

    /**
     * Validasi periode per F-07 (daftar putih).
     */
    private function validatePeriod(string $period): string
    {
        return in_array($period, self::VALID_PERIODS, true) ? $period : 'today';
    }

    /**
     * GET /api/stats
     * Ambil statistik pemakaian dari 9Router.
     * Cache 10 detik per F-08.
     */
    public function stats(Request $request, NineRouterClient $client): JsonResponse
    {
        $period = $this->validatePeriod($request->query('period', 'today'));
        $cacheKey = "stats:{$period}";

        try {
            $data = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_DURATION), function () use ($client, $period) {
                return $client->get(config('services.ninerouter.stats_path'), $period);
            });

            return response()->json($data);
        } catch (RuntimeException $e) {
            // F-09: Kegagalan upstream sebagai 502 tanpa membocorkan kredensial
            return response()->json([
                'error' => 'Gagal mengambil data dari 9Router',
                'detail' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * GET /api/chart
     * Ambil grafik tren dari 9Router (v1.1+).
     * Cache 10 detik per F-08.
     */
    public function chart(Request $request, NineRouterClient $client): JsonResponse
    {
        $period = $this->validatePeriod($request->query('period', 'today'));
        $cacheKey = "chart:{$period}";

        try {
            $data = Cache::remember($cacheKey, now()->addSeconds(self::CACHE_DURATION), function () use ($client, $period) {
                return $client->get(config('services.ninerouter.chart_path'), $period);
            });

            return response()->json($data);
        } catch (RuntimeException $e) {
            // F-09: Kegagalan upstream sebagai 502 tanpa membocorkan kredensial
            return response()->json([
                'error' => 'Gagal mengambil grafik dari 9Router',
                'detail' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * POST /api/wake-up
     * Kirim HTTP request ke 9Router supaya instance Blitz.cloud yang sedang
     * sleep ikut bangun. Tunggu sampai upstream merespons (termasuk cold start).
     * Rate limit 1 request per 60 detik supaya tidak spam cold start.
     */
    public function wakeUp(Request $request, NineRouterClient $client): JsonResponse
    {
        $rateKey = 'ninerouter:wakecooldown';
        if (Cache::has($rateKey)) {
            return response()->json([
                'status' => 'cooldown',
                'message' => 'Baru saja dijalankan. Tunggu beberapa saat lalu coba lagi.',
                'retry_after' => Cache::get($rateKey . ':ttl', 60),
            ], 429);
        }

        $url = rtrim((string) config('services.ninerouter.url'), '/');
        if ($url === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'NINE_ROUTER_URL belum dikonfigurasi.',
            ], 500);
        }

        $started = microtime(true);

        try {
            $client->wakeUp();

            $elapsed = round(microtime(true) - $started, 1);
            Cache::put($rateKey, true, now()->addSeconds(60));
            Cache::put($rateKey . ':ttl', 60, now()->addSeconds(60));

            return response()->json([
                'status' => 'awake',
                'elapsed_seconds' => $elapsed,
                'message' => '9Router merespons. Instance aktif.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membangunkan 9Router: ' . $e->getMessage(),
            ], 504);
        }
    }
}
