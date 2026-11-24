<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ASVS 1.3.1: aplikasi tidak punya editor WYSIWYG; HTML pada teks bebas
     * (ringkasan, catatan) tidak disanitasi tetapi di-escape saat dirender.
     */
    public function test_free_text_fields_render_html_as_inert_text(): void
    {
        $payload = '<img src=x onerror=alert(1)><b>tebal</b>';
        $proposal = Proposal::factory()->approved()->registered()->create(['summary' => $payload]);
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $keys = array_keys(Review::ASPECTS);
        $proposal->reviews()->create([
            'reviewer_id' => $reviewer->id,
            'scores' => array_fill_keys($keys, 80),
            'values' => Review::valuesFromScores(array_fill_keys($keys, 80)),
            'notes' => array_fill_keys($keys, $payload),
            'recommendation_note' => $payload,
            'signed_at' => now(),
        ]);

        $html = $this->actingAs($reviewer)->get(route('proposals.show', $proposal))->assertOk()->getContent();

        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<b>tebal</b>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function test_pages_send_csp_that_blocks_inline_script(): void
    {
        $response = $this->get(route('login'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * ASVS 3.4.1: HSTS (min. 1 tahun, termasuk subdomain) pada semua respons HTTPS,
     * termasuk rute di luar grup web (/up) dan 404.
     */
    public function test_https_responses_send_hsts_header(): void
    {
        foreach (['/login', '/up', '/tidak-ada'] as $path) {
            $this->get("https://localhost{$path}")
                ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }

    /**
     * ASVS 3.4.1: di belakang reverse proxy yang menghentikan TLS, request sampai
     * ke aplikasi sebagai HTTP + X-Forwarded-Proto: https; HSTS tetap harus terkirim.
     */
    public function test_hsts_is_sent_behind_tls_terminating_proxy(): void
    {
        foreach (['/login', '/up', '/tidak-ada'] as $path) {
            $this->withHeaders(['X-Forwarded-Proto' => 'https'])
                ->get("http://localhost{$path}")
                ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }

    /**
     * ASVS 3.5.1: aplikasi tidak mengandalkan CORS preflight, jadi setiap rute
     * pengubah state (POST/PUT/PATCH/DELETE) harus menolak request lintas-origin
     * tanpa token anti-forgery. Middleware CSRF dilewati otomatis saat env=testing,
     * sehingga env dipaksa di sini agar penegakannya benar-benar teruji.
     */
    public function test_every_state_changing_route_rejects_requests_without_csrf_token(): void
    {
        $this->app['env'] = 'production';
        $checked = 0;

        foreach (app('router')->getRoutes() as $route) {
            $methods = array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']);

            if ($methods === [] || ! in_array('web', $route->gatherMiddleware(), true)) {
                continue;
            }

            $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());

            // Request forgery lintas-situs: browser menandai Sec-Fetch-Site: cross-site, tanpa token.
            $this->call(reset($methods), $uri, [], [], [], ['HTTP_SEC_FETCH_SITE' => 'cross-site'])
                ->assertStatus(419);
            $checked++;
        }

        $this->assertGreaterThanOrEqual(8, $checked);
    }

    /**
     * ASVS 3.5.3: fungsi sensitif hanya terjangkau lewat POST/PUT/PATCH/DELETE;
     * GET dan HEAD ke URI tersebut ditolak (405), bukan dijalankan.
     */
    public function test_sensitive_actions_are_not_reachable_with_safe_methods(): void
    {
        $getUris = [];
        $unsafeOnly = [];

        foreach (app('router')->getRoutes() as $route) {
            if (in_array('GET', $route->methods(), true)) {
                $getUris[] = $route->uri();
            } else {
                $unsafeOnly[] = $route;
            }
        }

        $checked = 0;

        foreach ($unsafeOnly as $route) {
            if (in_array($route->uri(), $getUris, true)) {
                continue;
            }

            $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
            $this->call('GET', $uri)->assertStatus(405);
            $this->call('HEAD', $uri)->assertStatus(405);
            $checked++;
        }

        $this->assertGreaterThanOrEqual(4, $checked);
    }

    /**
     * ASVS 3.5.3: sebaliknya, setiap rute GET/HEAD tidak mengubah data apa pun.
     */
    public function test_safe_method_routes_do_not_modify_data(): void
    {
        Storage::fake();
        Storage::put('proposals/a.pdf', '%PDF-1.4');
        $proposal = Proposal::factory()->approved()->registered()->create(['file_path' => 'proposals/a.pdf']);
        $users = [
            $proposal->issuer,
            User::factory()->kaprodi()->create(),
            User::factory()->sekretaris()->create(),
            User::factory()->create(['role' => 'reviewer']),
        ];
        $snapshot = fn () => collect(['users', 'proposals', 'reviews', 'budgets'])
            ->map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson())
            ->all();
        $before = $snapshot();
        $visited = 0;

        foreach (app('router')->getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = '/'.ltrim(preg_replace('/\{[^}]+\}/', (string) $proposal->id, $route->uri()), '/');

            foreach ($users as $user) {
                $this->actingAs($user)->call('GET', $uri);
                $this->actingAs($user)->call('HEAD', $uri);
                $visited++;
            }
        }

        $this->assertGreaterThan(20, $visited);
        $this->assertSame($before, $snapshot());
    }

    public function test_request_with_valid_csrf_token_is_not_rejected_as_forgery(): void
    {
        $this->app['env'] = 'production';

        $this->withSession(['_token' => 'token-valid'])
            ->post('/login', ['_token' => 'token-valid'])
            ->assertStatus(302);
    }

    public function test_application_sends_no_permissive_cors_headers(): void
    {
        $response = $this->withHeaders(['Origin' => 'https://evil.example'])->get(route('login'));

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
        $response->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    /**
     * ASVS 1.3.2: kode aplikasi tidak memakai eval()/eksekusi kode dinamis,
     * dan CSP tidak mengizinkan 'unsafe-eval' di browser.
     */
    public function test_application_avoids_dynamic_code_execution(): void
    {
        $forbidden = '/\b(eval|assert|create_function|shell_exec|exec|system|passthru|proc_open|popen|unserialize)\s*\(|Blade::render|compileString|\{!!|new\s+Function|\binclude(_once)?\s+\$|\brequire(_once)?\s+\$/';
        $offenders = [];

        foreach (['app', 'routes', 'bootstrap', 'config', 'database', 'resources'] as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS)) as $file) {
                if (preg_match('/\.(php|js)$/', $file->getFilename()) && preg_match($forbidden, file_get_contents($file->getPathname()))) {
                    $offenders[] = $file->getPathname();
                }
            }
        }

        $this->assertSame([], $offenders);
        $this->assertStringNotContainsString('unsafe-eval', $this->get(route('login'))->headers->get('Content-Security-Policy'));
    }

    /**
     * ASVS 1.5.1: aplikasi tidak mem-parse XML sama sekali (tidak ada permukaan XXE),
     * dan unggahan XML/XXE ditolak karena hanya PDF yang diterima.
     */
    public function test_application_has_no_xml_parsing_and_rejects_xml_uploads(): void
    {
        $xmlApi = '/simplexml_load|DOMDocument|XMLReader|xml_parser_create|libxml_|loadXML|SoapClient|XSLTProcessor|PhpWord|PhpSpreadsheet|ZipArchive/i';
        $offenders = [];

        foreach (['app', 'routes', 'bootstrap'] as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS)) as $file) {
                if (preg_match('/\.php$/', $file->getFilename()) && preg_match($xmlApi, file_get_contents($file->getPathname()))) {
                    $offenders[] = $file->getPathname();
                }
            }
        }

        $this->assertSame([], $offenders);

        $xxe = '<?xml version="1.0"?><!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><x>&e;</x>';
        Storage::fake();

        $this->actingAs(User::factory()->create())->post('/proposals', [
            'title' => 'Judul yang cukup panjang',
            'summary' => str_repeat('a', 60),
            'budget' => '15000000',
            'file' => UploadedFile::fake()->createWithContent('proposal.xml', $xxe),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, Proposal::count());
    }

    /**
     * ASVS 3.2.1: berkas unggahan pengguna dikirim sebagai attachment, sehingga
     * walau isinya HTML (mis. polyglot yang lolos validasi), browser tidak merendernya.
     */
    public function test_uploaded_file_is_served_as_attachment_not_rendered(): void
    {
        Storage::fake();
        Storage::put('proposals/evil.pdf', '<html><script>alert(document.cookie)</script></html>');
        $proposal = Proposal::factory()->create(['file_path' => 'proposals/evil.pdf']);

        $response = $this->actingAs($proposal->issuer)->get(route('proposals.file', $proposal))->assertOk();

        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * ASVS 3.2.1: berkas privat tidak bisa diminta langsung lewat route storage
     * bawaan Laravel tanpa URL bertanda tangan.
     */
    public function test_private_storage_cannot_be_requested_directly(): void
    {
        Storage::fake('local', ['serve' => true]);
        Storage::put('proposals/evil.pdf', '<html><script>alert(1)</script></html>');

        $this->get('/storage/proposals/evil.pdf')->assertForbidden();
        $this->put('/storage/proposals/evil.pdf?upload=1', [])->assertForbidden();
    }

    /**
     * ASVS 3.2.2: JavaScript sisi klien menulis teks ke DOM hanya lewat
     * textContent/createElement, tidak pernah lewat sink yang mem-parse HTML.
     */
    public function test_client_side_javascript_renders_text_safely(): void
    {
        $htmlSinks = '/innerHTML|outerHTML|insertAdjacentHTML|document\.write|createContextualFragment|\.html\(|x-html|v-html|dangerouslySetInnerHTML/';
        $offenders = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path(), \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (preg_match('/\.(js|ts|vue|php)$/', $file->getFilename()) && preg_match($htmlSinks, file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders);
        $this->assertStringContainsString('.textContent =', file_get_contents(resource_path('js/app.js')));
    }

    /**
     * ASVS 3.3.1: setiap cookie ber-atribut Secure dan bernama dengan prefix __Host- atau __Secure-.
     */
    public function test_cookies_are_secure_and_prefixed(): void
    {
        // Lewat HTTP biasa: Secure harus tetap ada, bukan hanya saat request kebetulan HTTPS.
        $cookies = $this->get(route('login'))->headers->getCookies();

        $this->assertSame(['__Host-review-proposal-session'], array_map(fn ($cookie) => $cookie->getName(), $cookies));

        foreach ($cookies as $cookie) {
            $this->assertTrue($cookie->isSecure(), "{$cookie->getName()} tanpa atribut Secure");
            $this->assertMatchesRegularExpression('/^__(Host|Secure)-/', $cookie->getName(), "{$cookie->getName()} tanpa prefix __Host-/__Secure-");
        }
    }

    public function test_user_input_is_escaped_in_html(): void
    {
        $payload = '"><script>alert(1)</script>';

        $this->from('/register')->followingRedirects()
            ->post('/register', ['name' => $payload])
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)', false);
    }
}
