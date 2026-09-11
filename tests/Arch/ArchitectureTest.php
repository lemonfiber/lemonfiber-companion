<?php

declare(strict_types=1);

// The architecture in ARCHITECTURE.md, made executable.
//
// Every rule here is one a reviewer would otherwise have to hold in their head
// on every pull request. Reviewers are inconsistent about that and a test is
// not, which is the whole argument for writing them down as code.

// ---------------------------------------------------------------------------
// The dependency rule: arrows point inward, and never back out.
// ---------------------------------------------------------------------------

arch('a feature never reaches an adapter')
    ->expect('App\Companion')
    ->not->toUse('App\Adapters');

arch('a port depends on nothing but PHP and its own values')
    ->expect('App\Contracts')
    ->not->toUse(['App\Companion', 'App\Adapters', 'Illuminate', 'Native', 'Lemonfiber\Sdk', 'Saloon']);

arch('nothing depends on an adapter except the bindings that install it')
    ->expect('App\Adapters')
    ->not->toBeUsedIn(['App\Companion', 'App\Contracts']);

// ---------------------------------------------------------------------------
// N1-R16 — the SDK is the only way out.
// ---------------------------------------------------------------------------

arch('the SDK is named in exactly one place')
    ->expect('Lemonfiber\Sdk')
    ->toOnlyBeUsedIn('App\Adapters\Stack');

arch('no HTTP client reaches the application')
    ->expect(['GuzzleHttp', 'Saloon', 'Symfony\Component\HttpClient'])
    ->not->toBeUsedIn(['App\Companion', 'App\Contracts', 'App\Support']);

arch('nothing opens a socket by hand')
    ->expect(['curl_init', 'curl_exec', 'fsockopen', 'stream_socket_client', 'file_get_contents'])
    ->not->toBeUsed();

// ---------------------------------------------------------------------------
// Hidden dependencies: a constructor should declare what a class needs.
// ---------------------------------------------------------------------------

arch('a feature asks for what it needs rather than reaching for it')
    ->expect(['app', 'resolve', 'Illuminate\Support\Facades', 'Illuminate\Container'])
    ->not->toBeUsedIn(['App\Companion', 'App\Contracts', 'App\Support']);

arch('configuration is read from config, never from the environment')
    ->expect('env')
    ->toOnlyBeUsedIn('Config');

// ---------------------------------------------------------------------------
// Shape.
// ---------------------------------------------------------------------------

arch('every class is final')
    ->expect('App')
    ->toBeFinal();

// A screen's state is what the renderer reads on re-render, so components are
// the one mutable shape here. The exemption is named rather than left as a gap:
// anything else that wants to be mutable is caught by this test and is probably
// the wrong design.
arch('everything is readonly except the components the renderer re-reads')
    ->expect('App')
    ->toBeReadonly()
    ->ignoring('App\Companion');

arch('a port is an interface')
    ->expect('App\Contracts')
    ->toBeInterfaces()
    ->ignoring('App\Contracts\Values');

arch('a view model carries no behaviour')
    ->expect('App\Companion')
    ->classes()
    ->toHaveSuffix('View')
    ->toBeReadonly()
    ->ignoring(['App\Companion\Shared']);

// ---------------------------------------------------------------------------
// Leftovers and silence.
// ---------------------------------------------------------------------------

arch('no debugging survives a commit')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit'])
    ->not->toBeUsed();

arch('nothing is silenced')
    ->expect('App')
    ->not->toUse('@');

arch('the application holds no mutable global state')
    ->expect('App')
    ->not->toHaveStaticProperties();

// ---------------------------------------------------------------------------
// The framework's own preset, which catches a long tail cheaply.
// ---------------------------------------------------------------------------

arch()->preset()->php();
arch()->preset()->security();
