<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('router:wake')]
#[Description('Ping 9Router to keep instance awake')]
class WakeRouter extends Command
{
    public function handle()
    {
        $url = config('services.ninerouter.url');

        if (!$url) {
            $this->error('NINE_ROUTER_URL tidak dikonfigurasi.');
            return 1;
        }

        try {
            $res = Http::withUserAgent('mcx9route/1.0')
                ->timeout(30)
                ->get($url . '/');

            $this->info("✓ 9Router pinged (status {$res->status()})");
            return 0;
        } catch (\Throwable $e) {
            $this->warn("⏳ Ping timed out atau gagal (expected saat cold start): {$e->getMessage()}");
            return 0; // jangan fail supaya scheduler terus berjalan
        }
    }
}
