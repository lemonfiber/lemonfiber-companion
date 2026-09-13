<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\ThemeToken;
use Native\Mobile\Edge\TailwindParser;
use Tests\Support\Edge;

// The one line that connects the design module to the renderer.
//
// `Theme::resolver()` can be correct and the application still render without
// an accent: EDGE consults a resolver the application registers, and nothing
// fails when none is. The module's own tests prove the answer; only a booted
// application proves the answer is being asked for.
//
// This is the composition root's failure mode exactly, which is why it is here
// rather than in the module: no test below this one can see it, because the
// module is not allowed to know the parser exists at boot.

it('G8 — every asserted token resolves once the application has booted', function (): void {
    $classes = array_map(
        static fn(ThemeToken $token): string => sprintf('bg-theme-%s', $token->value),
        ThemeToken::cases(),
    );

    $dropped = Edge::unsupportedClasses('theme/asserted', $classes);

    expect($dropped)->toBe([], sprintf(
        "EDGE dropped these after boot:\n  %s\n\n"
        . 'A dropped class is not an error anywhere — it is parsed, found to mean '
        . 'nothing, and discarded, so the screen renders without its accent and says '
        . 'nothing. It means no theme resolver reached the parser: check that '
        . 'CompositionRoot::boot() still calls TailwindParser::setThemeResolver() '
        . '(G8, F3).',
        implode("\n  ", $dropped),
    ));
});

it('DES-R24 — a token the platform owns is still dropped, and reported', function (): void {
    // The rule in tests/Templates is only worth having if an unmapped token
    // really is reported. Registering a resolver is exactly the change that
    // could make everything resolve — a resolver answering a hex for any string
    // would pass the test above and silence the one that matters.
    $dropped = Edge::unsupportedClasses('theme/unmapped', ['bg-theme-background text-theme-on-surface']);

    expect($dropped)->toBe(['bg-theme-background', 'text-theme-on-surface'], sprintf(
        "Expected both to be reported as unknown, got:\n  %s\n\n"
        . 'These name the platform\'s own theme roles, which this surface deliberately '
        . 'does not assert — the reader\'s light, dark and contrast settings decide them. '
        . 'A resolver that answers for them has stopped being the narrow one '
        . 'surface-mapping.md describes (DES-R24, DES-R26).',
        implode("\n  ", $dropped),
    ));
});

it('DES-R24 — the accent paints the brand colour, not merely something', function (): void {
    $parsed = TailwindParser::parse(sprintf('bg-theme-%s', ThemeToken::Accent->value));

    expect($parsed['bg'] ?? null)->toBe(ThemeToken::Accent->hex());

    // No dark companion, which is the decision rather than an oversight: the
    // parser emits one only when a dark resolver is registered, and ink on
    // lemon measures 10.9:1 whichever way the reader has their phone set.
    expect($parsed['dark'] ?? null)->toBeNull();
});

it('DES-R24 — the palette is still ours after another package sets its own', function (): void {
    // The assertion above was decided by luck until `nativephp/mobile-ui`
    // arrived. That package sets a light resolver *and* a dark one in its own
    // boot; `TailwindParser` holds one of each and keeps whichever was set
    // last; and provider discovery order is not something either package
    // chooses — it differs between a fresh `composer install` and an
    // incremental one. The assertion above passed locally and failed in CI on
    // the same commit, which is what that looks like from the outside.
    //
    // So this plants the rival rather than hoping to be second. A foreign
    // resolver answering for every token would make the whole application paint
    // somebody else's palette, and a foreign *dark* resolver is worse than it
    // sounds: it is additive, so every `bg-theme-*` silently gains a companion
    // nothing here measured.
    // Answering for every token, which is what a rival resolver does: the
    // parser hands it a bare name and it decides. Naming the parameter and not
    // reading it is the point — the hex comes back whatever was asked.
    TailwindParser::setThemeResolver(static fn(): string => '#ff00ff');
    TailwindParser::setThemeDarkResolver(static fn(): string => '#00ff00');
    TailwindParser::clearCache();

    TheTheme::paint();
    TailwindParser::clearCache();

    $parsed = TailwindParser::parse(sprintf('bg-theme-%s', ThemeToken::Accent->value));

    expect($parsed['bg'] ?? null)->toBe(ThemeToken::Accent->hex())
        ->and($parsed['dark'] ?? null)->toBeNull();
});
