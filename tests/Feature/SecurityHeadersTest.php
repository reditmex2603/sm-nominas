<?php

use App\Models\User;
use Illuminate\Support\Facades\Vite;

test('las cabeceras de seguridad fijas se aplican a todas las respuestas', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('el nonce CSP se genera y se inyecta en los atributos nonce del HTML', function () {
    $admin = User::factory()->admin()->create();

    $respuesta = $this->actingAs($admin)->get(route('dashboard'));

    $html = $respuesta->getContent();
    $nonce = Vite::cspNonce();

    expect($nonce)->not->toBeNull()
        ->and($html)->toContain('nonce="'.$nonce.'"')
        ->and(strlen($nonce))->toBe(40);
});

test('con CSP habilitado se emite Content-Security-Policy con el nonce en script-src', function () {
    config(['security.csp.enabled' => true]);
    config(['security.csp.script-src' => "'self' 'unsafe-inline'"]);
    config(['security.csp.style-src' => "'self' 'unsafe-inline'"]);

    $admin = User::factory()->admin()->create();

    $respuesta = $this->actingAs($admin)->get(route('dashboard'));

    $csp = $respuesta->headers->get('Content-Security-Policy');
    $nonce = Vite::cspNonce();

    expect($csp)->not->toBeNull()
        ->and($csp)->toContain("script-src 'self' 'nonce-{$nonce}'")
        ->and($csp)->toContain("style-src 'self' 'nonce-{$nonce}'")
        ->and($csp)->toContain("object-src 'none'")
        ->and($csp)->toContain("frame-ancestors 'none'");
});
