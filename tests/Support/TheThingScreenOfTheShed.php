<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AFootprint;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\WhatIsAlreadyRunning;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Kernel\Api\WhatTheVerbCameTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhereAServiceEndedUp;
use Modules\Kernel\Api\WhereTheServicesEndedUp;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatToDoWithThis;

use function str_repeat;

use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatRehearses;
use Tests\Support\Fakes\AStackThatSaysWhatItWaitsOn;
use Tests\Support\Fakes\AStackThatSupervises;
use Tests\Support\Fakes\StacksInMemory;

/** The screen about one thing on the shed's stack, which a verb is sent from and followed on. */
final readonly class TheThingScreenOfTheShed
{
    /** The machine the verbs here are sent to. */
    public static function theMachine(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat('8', Nonce::SHORTEST))),
            StackName::of('The shed'),
            Address::of('https://192.168.1.45:8443'),
            Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)),
        );
    }

    /** The screen about `sonarr`, or the thing named, on a machine this device is signed in to. */
    public static function theScreen(
        AStackThatSupervises $supervising,
        string $named = 'sonarr',
        ?AKeychainInMemory $keychain = null,
        ?AStackThatSaysWhatItWaitsOn $hearing = null,
    ): WhatToDoWithThis {
        $stack = self::theMachine();
        $keychain ??= AKeychainInMemory::working();
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

        $screen = new WhatToDoWithThis(
            $supervising,
            AStackThatRehearses::with(WhatStartingItWouldComeTo::rehearsed(Services::none(), TheServicesLeftOut::of(), AFootprint::estimated(0, Services::none()), WhatIsAlreadyRunning::these(Services::none()))),
            $keychain,
            AroundThePhone::holding(StacksInMemory::holding($stack)),
            new AppsSettingsThatOpen(),
            $hearing ?? AStackThatSaysWhatItWaitsOn::saying(),
            AroundThePhone::listening(),
            WhatThePhoneKeeps::noListingYet(),
        );
        $screen->setParams(['stack' => $stack->id()->stored(), 'service' => $named]);

        return $screen;
    }

    /**
     * A stack running a stopped Sonarr, which says a verb came to `$became`.
     *
     * @param HowAgreedWorkIsGoing<WhatTheVerbCameTo> $became
     */
    public static function aStoppedSonarrThatCameTo(HowAgreedWorkIsGoing $became): AStackThatSupervises
    {
        return AStackThatSupervises::with(WhatAMachineRuns::oneThing('sonarr', HowAServiceRuns::Stopped, HowTheStackIsRunning::Partial))
            ->whichCameTo($became);
    }

    /** A report of a verb that ran, with the services it waited for. */
    public static function aVerbThatRan(WhereAServiceEndedUp ...$services): WhatTheVerbCameTo
    {
        return WhatTheVerbCameTo::reported(
            WhetherItWasRehearsed::CarriedOut,
            WhereTheServicesEndedUp::of(...$services),
            TheServicesLeftOut::of(),
            ThePortsHeld::of(),
            TheStackEdits::none(),
            TheCommandLine::of('docker', 'compose', 'up', '-d'),
        );
    }

    /**
     * Start Sonarr from the screen, which needs no yes, and draw what that came to.
     *
     * @return list<string>
     */
    public static function whatStartingSonarrDrew(WhatToDoWithThis $screen): array
    {
        $screen->wouldYouLike(WhatToDoWithIt::Start->value);
        $screen->whileItSettles();

        // The frame after the tick reads what is running, which the verb changed,
        // and the frame after that asks what the verb came to.
        return WhatTheDeviceWouldDraw::onTheSecondFrame($screen)->said();
    }
}
