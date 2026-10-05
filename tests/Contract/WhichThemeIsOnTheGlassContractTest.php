<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\Api\WhoseTheme;
use Modules\Kernel\Api\SecureStorage;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AThemeOnTheGlass;

// The WhichThemeIsOnTheGlass contract, run against the theme the composition
// root paints and against the fake: before any screen is drawn the glass shows
// the member's theme, and after that the theme last put on it.

/**
 * Each glass, built showing one theme after the other was on it, or showing nothing yet.
 *
 * @return array<string, array{callable(): WhichThemeIsOnTheGlass, callable(WhoseTheme): WhichThemeIsOnTheGlass}>
 */
function glasses(): array
{
    $painted = static fn(): TheTheme => new TheTheme(static fn(): SecureStorage => AKeychainInMemory::working());

    return [
        'TheTheme' => [
            $painted,
            static function (WhoseTheme $theme) use ($painted): WhichThemeIsOnTheGlass {
                $glass = $painted();

                foreach (WhoseTheme::cases() as $before) {
                    if ($before !== $theme) {
                        $glass->paint($before);
                    }
                }

                $glass->paint($theme);

                return $glass;
            },
        ],
        'AThemeOnTheGlass' => [
            AThemeOnTheGlass::beforeAnyScreen(...),
            AThemeOnTheGlass::showing(...),
        ],
    ];
}

foreach (glasses() as $name => [$beforeAnyScreen, $showing]) {
    it(sprintf('%s shows the member\'s theme before any screen is drawn', $name), function () use ($beforeAnyScreen): void {
        expect($beforeAnyScreen()->whose())->toBe(WhoseTheme::Member);
    });

    it(sprintf('%s shows the theme last put on it', $name), function (WhoseTheme $theme) use ($showing): void {
        expect($showing($theme)->whose())->toBe($theme);
    })->with(WhoseTheme::cases());
}
