<?php

declare(strict_types=1);

namespace Tests\Support;

use function app;

use Illuminate\Contracts\Translation\Translator;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\ProposedChange;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatItHoldsNow;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatThisStackIsSetTo;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AStackThatIsSet;
use Tests\Support\Fakes\AStackThatSaysHowItRunsWithout;
use Tests\Support\Fakes\AStackThatSaysWhatSetupSettled;
use Tests\Support\Fakes\AStackToldToChangeSomething;
use Tests\Support\Fakes\StacksInMemory;

/**
 * The settings screen of the loft, with a fake for everything it asks and whichever a test hands it.
 *
 * Shared by `SeeingWhatAStackIsSetToTest`, `SeeingHowAStackRunsWithoutLemonfiberTest` and `SeeingWhatSetupSettledTest`.
 */
final readonly class TheSettingsOfTheLoft
{
    /** The screen, signed in unless a test says otherwise. */
    public static function screen(
        AStackThatIsSet $arranging,
        ?AStackToldToChangeSomething $adjusting = null,
        ?AKeychainInMemory $keychain = null,
        bool $signedIn = true,
        ?AStackThatSaysHowItRunsWithout $without = null,
        ?AShareSheetThatWasOffered $sheet = null,
        ?AStackThatSaysWhatSetupSettled $settled = null,
    ): WhatThisStackIsSetTo {
        $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
        $keychain ??= AKeychainInMemory::working();

        if ($signedIn) {
            $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
        }

        $screen = new WhatThisStackIsSetTo(
            $arranging,
            $adjusting ?? AStackToldToChangeSomething::saying(WhereTheChangeStands::at(ProposedChange::of('BIND', 'any', WhatItHoldsNow::shown('lan'), Cost::Cheap), Stance::Pending, WhatAChangeComesTo::nothing())),
            $keychain,
            AroundThePhone::holding(StacksInMemory::holding($stack)),
            new AppsSettingsThatOpen(),
            AroundThePhone::listening(),
            $without ?? AStackThatSaysHowItRunsWithout::bundled(),
            $settled ?? AStackThatSaysWhatSetupSettled::setUp(),
            $sheet ?? AShareSheetThatWasOffered::working(),
            app(Translator::class),
        );
        $screen->setParams(['stack' => $stack->id()->stored()]);

        return $screen;
    }
}
