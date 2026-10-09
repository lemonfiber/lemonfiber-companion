<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowCurrentThisStackIs;

use function str_repeat;

use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatKeepsCurrent;
use Tests\Support\Fakes\StacksInMemory;

/**
 * The upkeep screen, about the one stack the files about that screen look at, and
 * the evening of updates it is shown.
 *
 * Shared by `SeeingHowCurrentAStackIsTest` and `SeeingWhatAnUpdateTakenHereCameToTest`.
 */
final readonly class TheUpkeepScreenOfTheLoft
{
    /** The machine whose upkeep this screen is about. */
    public static function theStackWhoseUpkeepIsRead(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat('g', Nonce::SHORTEST))),
            StackName::of('The loft'),
            Address::of('https://192.168.1.42:8443'),
            Fingerprint::of(str_repeat('a', Fingerprint::CHARACTERS)),
        );
    }

    /**
     * An update waiting: two services behind their pins, and the release carrying
     * those pins one the household will notice. Its history holds that release and
     * one before it nobody noticed.
     */
    public static function anEveningWorthSpending(HowTheNotesStand $notes = HowTheNotesStand::Current): Upkeep
    {
        return Upkeep::reported(
            AgainstThePins::UpdatesAvailable,
            Releases::these(
                Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
                Release::called('4.0.16', noticeable: false, withdrawn: false, delivers: WhatAReleaseDelivers::saidNothing()),
            ),
            Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
            Services::none(),
            self::whatLastNightCameTo(),
            $notes,
            TheStackEdits::none(),
        )->runningOn(Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')));
    }

    /**
     * What became of the last update: one service back, one that would not start.
     *
     * Two endings and not one, because the rule is that they stay told
     * apart — and two ways back, because a rollback and a
     * restore are not one offer.
     */
    public static function whatLastNightCameTo(): HowServicesTookIt
    {
        return HowServicesTookIt::these(
            HowAServiceTookIt::of(ServiceId::called('jellyfin'), HowItEnded::Updated, HowToUndoIt::Rollback),
            HowAServiceTookIt::of(ServiceId::called('sonarr'), HowItEnded::NotStarted, HowToUndoIt::Restore),
        );
    }

    /**
     * The screen, with a stack it knows and a keychain holding whatever a test says.
     */
    public static function theUpkeepScreen(
        AStackThatKeepsCurrent $keeping,
        ?AKeychainInMemory $keychain = null,
        bool $signedIn = true,
    ): HowCurrentThisStackIs {
        $stack = self::theStackWhoseUpkeepIsRead();
        $keychain ??= AKeychainInMemory::working();

        if ($signedIn) {
            $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
        }

        $screen = new HowCurrentThisStackIs($keeping, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), WhatThePhoneKeeps::noUpkeepYet());
        $screen->setParams(['stack' => $stack->id()->stored()]);

        return $screen;
    }

    /**
     * The update's own report, finished, saying this of each service it touched.
     *
     * @return HowAgreedWorkIsGoing<Upkeep>
     */
    public static function aReportSaying(HowServicesTookIt $went): HowAgreedWorkIsGoing
    {
        return HowAgreedWorkIsGoing::done(Upkeep::reported(AgainstThePins::Current, Releases::none(), Services::none(), Services::none(), $went, HowTheNotesStand::Current, TheStackEdits::none()));
    }

    /** The screen once 4.1.0 has been taken and the cadence has asked after it once. */
    public static function aScreenThatTookTheUpdate(AStackThatKeepsCurrent $keeping, ?AKeychainInMemory $keychain = null): HowCurrentThisStackIs
    {
        $screen = self::theUpkeepScreen($keeping, $keychain);
        $screen->wouldYouLike();
        $screen->agree();
        $screen->whileItRuns();

        return $screen;
    }
}
