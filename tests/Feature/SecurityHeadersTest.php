<?php

use App\Http\Middleware\AddSecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

it('sets security headers and authorizes scripts only with a per-response nonce', function () {
    $response = $this->get(route('home'));
    $contentSecurityPolicy = (string) $response->headers->get('Content-Security-Policy');
    $content = (string) $response->getContent();

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

    preg_match('/<script type="application\/ld\+json" nonce="([^"]+)">/', $content, $matches);
    preg_match('/(?:^|; )script-src ([^;]+)/', $contentSecurityPolicy, $scriptPolicy);
    preg_match_all('/<script\b[^>]*\bnonce="([^"]+)"/', $content, $scriptNonces);

    expect($matches[1] ?? null)->not->toBeEmpty()
        ->and($contentSecurityPolicy)->toContain("script-src 'self' 'nonce-".$matches[1]."'")
        ->and($contentSecurityPolicy)->toContain("frame-ancestors 'none'")
        ->and($scriptPolicy[1] ?? '')->not->toContain("'unsafe-inline'")
        ->and($scriptNonces[1] ?? [])->not->toBeEmpty()
        ->and(array_unique($scriptNonces[1]))->toBe([$matches[1]]);
});

it('sends HSTS only for secure production requests', function () {
    config(['app.env' => 'production']);

    $middleware = app(AddSecurityHeaders::class);
    $next = fn (): Response => response('ok');
    $secureResponse = $middleware->handle(Request::create('https://alfikar.test/'), $next);
    $insecureResponse = $middleware->handle(Request::create('http://alfikar.test/'), $next);

    expect($secureResponse->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000')
        ->and($insecureResponse->headers->has('Strict-Transport-Security'))->toBeFalse();
});

it('protects production session cookies with secure HTTP-only same-site attributes', function () {
    config([
        'app.env' => 'production',
        'session.secure' => true,
        'session.http_only' => true,
        'session.same_site' => 'lax',
    ]);

    $response = $this->get(route('admin.login'))->assertOk();
    $sessionCookie = collect($response->headers->getCookies())
        ->first(fn (Cookie $cookie): bool => $cookie->getName() === config('session.cookie'));

    expect($sessionCookie)->not->toBeNull()
        ->and($sessionCookie->isSecure())->toBeTrue()
        ->and($sessionCookie->isHttpOnly())->toBeTrue()
        ->and($sessionCookie->getSameSite())->toBe('lax');
});
