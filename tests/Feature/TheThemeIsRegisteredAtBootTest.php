<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\ThemeToken;
use Native\Mobile\Edge\TailwindParser;
use Tests\Support\Edge;

// Runs the composition root rather than reading it, so its mutants are judged
// here: see `scripts/mutation.php`. It is also the canary a mutation shard runs
// in place of the suite when it takes its coverage map from the tests job,
// because it boots the application: see scripts/patch_pest_mutate_shared_coverage.php.
pest()->group('holds:bootstrap/Composition', 'mutation-canary');

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

it('resolves every role once the application has booted', function (): void {
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
        . "CompositionRoot::boot() still registers TheTheme::paint() (G8, F3).",
        implode("\n  ", $dropped),
    ));
});

it('still drops, and reports, a token this surface does not assert', function (): void {
    // The rule in tests/Templates is only worth having if an unmapped token
    // really is reported. Registering a resolver is exactly the change that
    // could make everything resolve — a resolver answering a hex for any string
    // would pass the test above and silence the one that matters.
    $dropped = Edge::unsupportedClasses('theme/unmapped', ['bg-theme-background text-theme-on-surface']);

    expect($dropped)->toBe(['bg-theme-background', 'text-theme-on-surface'], sprintf(
        "Expected both to be reported as unknown, got:\n  %s\n\n"
        . 'These name mobile-ui\'s own roles, which this surface does not assert. A '
        . 'resolver that answers for them answers for any name at all.',
        implode("\n  ", $dropped),
    ));
});

it('paints each role with its light hex and gives a neutral its dark one', function (): void {
    $accent = TailwindParser::parse(sprintf('bg-theme-%s', ThemeToken::Accent->value));
    $text = TailwindParser::parse(sprintf('text-theme-%s', ThemeToken::Text->value));

    expect($accent['bg'] ?? null)->toBe(ThemeToken::Accent->light())
        ->and($accent['dark'] ?? null)->toBeNull()
        ->and($text['color'] ?? null)->toBe(ThemeToken::Text->light())
        ->and(data_get($text, 'dark.color'))->toBe(ThemeToken::Text->dark());
});

it('keeps the palette its own after another package sets one', function (): void {
    // mobile-ui sets a light and a dark resolver in its own boot, and the parser
    // keeps whichever was set last. A rival answering for every token is planted
    // here, and painting must replace both.
    TailwindParser::setThemeResolver(static fn(): string => '#ff00ff');
    TailwindParser::setThemeDarkResolver(static fn(): string => '#00ff00');
    TailwindParser::clearCache();

    TheTheme::paint();
    TailwindParser::clearCache();

    $parsed = TailwindParser::parse(sprintf('bg-theme-%s', ThemeToken::Surface->value));

    expect($parsed['bg'] ?? null)->toBe(ThemeToken::Surface->light())
        ->and(data_get($parsed, 'dark.bg'))->toBe(ThemeToken::Surface->dark());
});
