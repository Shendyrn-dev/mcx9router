<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BlitzMcpClient
{
    private function request(string $method, array $params = []): array
    {
        $url = config('services.blitz.mcp_url');
        $key = config('services.blitz.api_key');

        if (!$url || !$key) {
            throw new RuntimeException('BLITZ_MCP_URL atau BLITZ_API_KEY belum dikonfigurasi');
        }

        $res = Http::withHeaders([
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
        ])
        ->timeout(45)
        ->post(rtrim($url, '/'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $method,
                'arguments' => $params,
            ],
        ]);

        if (!$res->successful()) {
            throw new RuntimeException("Blitz MCP error: HTTP {$res->status()} body=" . substr($res->body(), 0, 500));
        }

        $json = $res->json();

        if (isset($json['error'])) {
            throw new RuntimeException('Blitz MCP error: ' . json_encode($json['error']));
        }

        $result = $json['result'] ?? null;
        if (is_array($result) && ($result['isError'] ?? false) === true) {
            $msg = $result['content'][0]['text'] ?? json_encode($result);
            throw new RuntimeException('Blitz MCP isError: ' . substr($msg, 0, 800));
        }

        // content[0].text berisi JSON string app detail
        if (isset($result['content'][0]['text'])) {
            $text = $result['content'][0]['text'];
            $decoded = json_decode($text, true);
            return is_array($decoded) ? $decoded : ['text' => $text, 'raw' => $result];
        }

        return is_array($json) ? $json : ['raw' => $res->body()];
    }

    public function listApps(): array
    {
        return $this->request('apps_list');
    }

    public function resumeApp(string $appId): array
    {
        return $this->request('apps_resume', ['app' => $appId]);
    }

    public function resumeConfiguredApp(): array
    {
        $appId = config('services.blitz.app_id');

        if (!$appId) {
            throw new RuntimeException('BLITZ_APP_ID belum dikonfigurasi');
        }

        return $this->resumeApp($appId);
    }
}
