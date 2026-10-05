<?php

declare(strict_types=1);

use Bootstrap\Composition\CompositionRoot;
use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\Theme;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\Api\WhoseTheme;
use Modules\Wayfinding\Api\WhoTheMenuIsFor;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\UI\Theme as WhatTheWidgetsPaintWith;
use Tests\Support\Imports;
use Tests\Support\OurCode;
use Tests\Support\Tree;

// Whose session a screen is drawn for chooses its theme, and nothing else
// does: no setting, no switch, nothing kept. A setting would have to name the
// choice to make it, so the choice is named only where it is decided and
// painted. Anything else naming it, a settings screen or a store, is a second
// way of choosing, and this refuses it by name.

/**
 * The names a theme is chosen or painted with, and the classes allowed to name each.
 *
 * @return array<class-string, list<class-string>>
 */
function whoMayChooseTheTheme(): array
{
    return [
        // The choice, declared by the design module and read from the session
        // by the answer the list of stacks reads.
        WhoseTheme::class => [WhoseTheme::class, ThemeToken::class, Theme::class, WhichThemeIsOnTheGlass::class, WhoTheMenuIsFor::class, TheTheme::class, CompositionRoot::class],
        // The resolver handed to the parser, and the parser and the widget
        // theme it is painted into.
        Theme::class => [TheTheme::class],
        TailwindParser::class => [TheTheme::class],
        WhatTheWidgetsPaintWith::class => [TheTheme::class],
    ];
}

it('names the theme a screen is drawn in only where whose session it is decides it', function (): void {
    $outside = [];

    foreach (OurCode::sourceFiles() as $file) {
        $named = Imports::of($file);
        $declared = Imports::declaredName($file);

        foreach (whoMayChooseTheTheme() as $choice => $allowed) {
            if (in_array($choice, $named, strict: true) && ! in_array($declared, $allowed, strict: true)) {
                $outside[] = sprintf('%s names %s', $declared, $choice);
            }
        }
    }

    expect($outside)->toBe([], sprintf(
        "These name the theme outside where it is decided:\n  %s\n\n"
        . 'A screen is drawn in the operator\'s theme for the operator\'s session and in '
        . 'the member\'s otherwise, and no setting chooses between them. Read whose '
        . 'theme it is from WhichThemeIsOnTheGlass, which the composition root answers.',
        implode("\n  ", $outside),
    ));
});

it('offers no theme in what the app says', function (string $locale): void {
    $catalogue = Tree::filesUnder(Tree::at(sprintf('lang/%s', $locale)), '.php');
    $offered = [];

    foreach ($catalogue as $file) {
        $said = file_get_contents($file);

        if (is_string($said) && preg_match('/\\b(theme|thema|dark mode|light mode|donkere modus|lichte modus)\\b/i', $said) === 1) {
            $offered[] = basename($file);
        }
    }

    expect($catalogue)->not->toBe([])
        ->and($offered)->toBe([]);
})->with(['en', 'nl']);
