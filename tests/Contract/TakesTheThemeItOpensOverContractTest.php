<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\TakesTheThemeItOpensOver;
use Modules\Design\Api\WhoseTheme;
use Modules\Kernel\Api\SecureStorage;
use Modules\Operator\Internal\Screens\HowThisPhoneIsSet;
use Modules\Operator\Internal\Screens\WhatIsNewOnEveryStack;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AScreenOpenedOverAnother;

// The TakesTheThemeItOpensOver contract, run against every screen that keeps
// the theme it opens over and against the fake: built as the router builds it
// and brought to the front, it is drawn in the theme already on the glass,
// whichever that is.

/** @return array<string, callable(): (NativeComponent&TakesTheThemeItOpensOver)> */
function screensThatKeepTheTheme(): array
{
    return [
        'HowThisPhoneIsSet' => static fn(): HowThisPhoneIsSet => app(HowThisPhoneIsSet::class),
        'WhatIsNewOnEveryStack' => static fn(): WhatIsNewOnEveryStack => app(WhatIsNewOnEveryStack::class),
        'AScreenOpenedOverAnother' => static fn(): AScreenOpenedOverAnother => new AScreenOpenedOverAnother(),
    ];
}

foreach (screensThatKeepTheTheme() as $name => $build) {
    it(sprintf('%s is drawn in the theme of the screen it opens over', $name), function (WhoseTheme $beneath) use ($build): void {
        $glass = new TheTheme(static fn(): SecureStorage => AKeychainInMemory::working());
        $glass->paint($beneath);

        $glass->forTheScreen($build());

        expect($glass->whose())->toBe($beneath);
    })->with(WhoseTheme::cases());
}
