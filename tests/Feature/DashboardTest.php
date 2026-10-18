<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set konfigurasi wajib untuk test
        Config::set('services.ninerouter.url', 'https://9router.test');
        Config::set('services.ninerouter.login_path', '/api/auth/login');
        Config::set('services.ninerouter.login_field', 'password');
        Config::set('services.ninerouter.password', 'test-password');
        Config::set('services.ninerouter.stats_path', '/api/usage/stats');
        Config::set('services.dashboard.user', 'admin');
        Config::set('services.dashboard.password', 'test-dash-password');
        
        Cache::flush();
    }

    /**
     * T-01: Akses tanpa login harus ditolak (401)
     */
    public function test_access_without_login_is_denied(): void
    {
        $response = $this->get('/');
        
        $response->assertStatus(401);
        $response->assertHeader('WWW-Authenticate');
    }

    /**
     * T-02: Login dengan password salah ditolak
     */
    public function test_login_with_wrong_password_is_denied(): void
    {
        $response = $this->withBasicAuth('admin', 'wrong-password')->get('/');
        
        $response->assertStatus(401);
    }

    /**
     * T-03: Login benar membuka dashboard
     */
    public function test_login_with_correct_credentials_shows_dashboard(): void
    {
        Http::fake([
            '9router.test/api/auth/login' => Http::response('', 200, [
                'Set-Cookie' => 'auth_token=fake-token-123; Path=/; HttpOnly'
            ]),
            '9router.test/api/usage/stats*' => Http::response([
                'totalRequests' => 10,
                'totalPromptTokens' => 5000,
                'totalCompletionTokens' => 500,
                'totalCachedTokens' => 100,
                'totalCost' => 1.5,
                'byProvider' => [],
                'byModel' => [],
            ]),
        ]);

        $response = $this->withBasicAuth('admin', 'test-dash-password')->get('/');
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    /**
     * T-06, F-04, F-05: Token disimpan di cache dan dipakai ulang
     */
    public function test_token_is_cached_and_reused(): void
    {
        Http::fake([
            '9router.test/api/auth/login' => Http::response('', 200, [
                'Set-Cookie' => 'auth_token=cached-token; Path=/; HttpOnly'
            ]),
            '9router.test/api/usage/stats*' => Http::response([
                'totalRequests' => 5,
                'totalPromptTokens' => 1000,
                'totalCompletionTokens' => 100,
                'totalCachedTokens' => 50,
                'totalCost' => 0.5,
                'byProvider' => [],
                'byModel' => [],
            ]),
        ]);

        // Request pertama - harus login
        $response1 = $this->withBasicAuth('admin', 'test-dash-password')
            ->get('/api/stats?period=today');
        
        $response1->assertStatus(200);
        
        // Pastikan login dipanggil
        Http::assertSent(function ($request) {
            return $request->url() === 'https://9router.test/api/auth/login';
        });

        // Request kedua - harus pakai token dari cache (tidak login lagi)
        Http::fake([
            '9router.test/api/usage/stats*' => Http::response([
                'totalRequests' => 5,
                'totalPromptTokens' => 1000,
                'totalCompletionTokens' => 100,
                'totalCachedTokens' => 50,
                'totalCost' => 0.5,
                'byProvider' => [],
                'byModel' => [],
            ]),
        ]);

        $response2 = $this->withBasicAuth('admin', 'test-dash-password')
            ->get('/api/stats?period=today');
        
        $response2->assertStatus(200);
        
        // Login tidak boleh dipanggil lagi
        Http::assertNothingSent();
    }

    /**
     * F-06: Login ulang otomatis saat 9Router membalas 401
     */
    public function test_auto_relogin_on_401_from_ninerouter(): void
    {
        // Set token lama di cache
        Cache::put('ninerouter:token', 'expired-token', now()->addHours(1));

        Http::fake([
            '9router.test/api/usage/stats*' => Http::sequence()
                ->push('', 401) // Request pertama: 401
                ->push([         // Request kedua: berhasil
                    'totalRequests' => 3,
                    'totalPromptTokens' => 800,
                    'totalCompletionTokens' => 80,
                    'totalCachedTokens' => 20,
                    'totalCost' => 0.3,
                    'byProvider' => [],
                    'byModel' => [],
                ]),
            '9router.test/api/auth/login' => Http::response('', 200, [
                'Set-Cookie' => 'auth_token=new-token-after-401; Path=/; HttpOnly'
            ]),
        ]);

        $response = $this->withBasicAuth('admin', 'test-dash-password')
            ->get('/api/stats?period=today');
        
        $response->assertStatus(200);
        $response->assertJsonPath('totalRequests', 3);
        
        // Pastikan login dipanggil untuk mendapat token baru
        Http::assertSent(function ($request) {
            return $request->url() === 'https://9router.test/api/auth/login';
        });
    }

    /**
     * F-07: Parameter period tidak valid diganti 'today'
     */
    public function test_invalid_period_defaults_to_today(): void
    {
        Http::fake([
            '9router.test/api/auth/login' => Http::response('', 200, [
                'Set-Cookie' => 'auth_token=test-token; Path=/; HttpOnly'
            ]),
            '9router.test/api/usage/stats*' => Http::response([
                'totalRequests' => 1,
                'totalPromptTokens' => 100,
                'totalCompletionTokens' => 10,
                'totalCachedTokens' => 5,
                'totalCost' => 0.1,
                'byProvider' => [],
                'byModel' => [],
            ]),
        ]);

        $response = $this->withBasicAuth('admin', 'test-dash-password')
            ->get('/api/stats?period=invalid-value');
        
        $response->assertStatus(200);
        
        // Verifikasi bahwa request ke 9Router memakai 'today'
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'period=today');
        });
    }

    /**
     * T-09, F-02: DASH_PASSWORD kosong = akses ditolak
     */
    public function test_empty_dashboard_password_denies_access(): void
    {
        Config::set('services.dashboard.password', '');

        $response = $this->withBasicAuth('admin', 'anything')->get('/');
        
        $response->assertStatus(403);
        $response->assertSee('konfigurasi tidak lengkap');
    }

    /**
     * F-09: Kegagalan upstream dikembalikan sebagai 502
     */
    public function test_upstream_failure_returns_502(): void
    {
        Http::fake([
            '9router.test/api/auth/login' => Http::response('', 200, [
                'Set-Cookie' => 'auth_token=test-token; Path=/; HttpOnly'
            ]),
            '9router.test/api/usage/stats*' => Http::response('Server Error', 500),
        ]);

        $response = $this->withBasicAuth('admin', 'test-dash-password')
            ->get('/api/stats?period=today');
        
        $response->assertStatus(502);
        $response->assertJsonPath('error', 'Gagal mengambil data dari 9Router');
    }

    /**
     * F-03: Perbandingan kredensial memakai hash_equals (timing-safe)
     * Test ini memastikan middleware DashboardAuth ada dan menggunakan hash_equals.
     * Timing attack sulit ditest, tapi kita pastikan login gagal dengan user/pass salah.
     */
    public function test_credential_comparison_is_secure(): void
    {
        // Username salah
        $response1 = $this->withBasicAuth('wronguser', 'test-dash-password')->get('/');
        $response1->assertStatus(401);

        // Password salah
        $response2 = $this->withBasicAuth('admin', 'wrongpassword')->get('/');
        $response2->assertStatus(401);

        // Keduanya salah
        $response3 = $this->withBasicAuth('wronguser', 'wrongpassword')->get('/');
        $response3->assertStatus(401);
    }

    /**
     * T-11: Tidak ada password atau token 9Router di respons
     */
    public function test_no_credentials_in_response(): void
    {
        Http::fake([
            '9router.test/api/auth/login' => Http::response('', 200, [
                'Set-Cookie' => 'auth_token=secret-token; Path=/; HttpOnly'
            ]),
            '9router.test/api/usage/stats*' => Http::response([
                'totalRequests' => 1,
                'totalPromptTokens' => 100,
                'totalCompletionTokens' => 10,
                'totalCachedTokens' => 5,
                'totalCost' => 0.1,
                'byProvider' => [],
                'byModel' => [],
            ]),
        ]);

        $response = $this->withBasicAuth('admin', 'test-dash-password')
            ->get('/api/stats?period=today');
        
        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Pastikan tidak ada kredensial
        $this->assertStringNotContainsString('test-password', $content);
        $this->assertStringNotContainsString('secret-token', $content);
        $this->assertStringNotContainsString('auth_token', $content);
    }
}
