<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ACommandToHand;
use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\AskingHowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\AskingWhatSetupSettled;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatWasFoundOfTheAreasLeftAlone;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheSettingsRead;
use Modules\Operator\Internal\ProposesAChange;
use Modules\Operator\Internal\ReadsHowItRunsWithout;
use Modules\Operator\Internal\ReadsWhatSetupSettled;
use Modules\Operator\Internal\ViewModels\WhatThisStackIsSetToTurnedOutToBe;
use Modules\Operator\Internal\WhatTheSheetSaid;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\ReadsAStackOnceAFrame;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Everything this stack is set to, as the stack itself lists it.
 *
 * **The screen holds no list of settings and this is the point of it.** What
 * is drawn is the listing that came back, whatever was in it. An app that knew
 * the settings it could show would show a subset the day the stack gained one,
 * would show it silently, and would leave the operator to conclude the setting
 * does not exist.
 *
 * So there is no allow-list here, no ordering, no grouping and no labels of
 * this app's own: a key and what it holds, in the order the stack said them.
 * Anything prettier would be this side deciding something the other side owns.
 *
 * **What setup settled is drawn above the listing, read-only.** It is read
 * from the stack, and where setup is not finished the screen says to finish it
 * at the machine; setup is never offered here.
 *
 * **{@see Concealed} because a withheld row is still about a credential.** The
 * stack withholds the value and sends a note in its place, so nothing secret
 * reaches this screen — but the *names* do, and a task-switcher snapshot of a
 * list of credential keys is a list of what this household holds and where.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatThisStackIsSetTo extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use ReadsAStackOnceAFrame;
    use ReadsHowItRunsWithout;
    use ReadsWhatSetupSettled;
    use ProposesAChange, FindsItsWayAround {
        ProposesAChange::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }

    /** The key for the name the share sheet shows the words under. */
    private const string COPY_NAMED = 'without.copy_named';

    public ?WhatThisStackIsSetToTurnedOutToBe $answered = null;

    /** The catalogue key for what handing the words over came to, where they have been. */
    public ?string $copied = null;

    public function __construct(
        private readonly Arranging $arranging,
        protected readonly Adjusting $adjusting,
        protected readonly SettingWhatServicesRead $tuning,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly AskingHowItRunsWithoutLemonfiber $askingWithout,
        protected readonly AskingWhatSetupSettled $askingSettled,
        private readonly Sharing $sharing,
        private readonly Translator $catalogue,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /**
     * Ask again.
     *
     * Dropping the held answer rather than re-reading here, so the next thing
     * that wants it does the asking — one path to the stack instead of two
     * that can disagree about what happened.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->proposed = null;
        $this->copied = null;
        $this->forgetHowItRunsWithout();
        $this->forgetWhatSetupSettled();
    }

    /**
     * Hand the words that bring the stack up without lemonfiber to the device's own sharing, to copy out.
     *
     * Only words the stack gave are offered; the sheet is the platform's and
     * where they go is the operator's choice.
     */
    public function copyTheWords(): void
    {
        $how = $this->heardWithout;

        if (! $how instanceof HowItRunsWithoutLemonfiber) {
            return;
        }

        $named = $this->catalogue->get(self::COPY_NAMED);

        $this->copied = $this->sharing->offerToCopy(ACommandToHand::of($how->plainUp(), is_string($named) ? $named : self::COPY_NAMED))->either(
            over: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('without.copied'),
            refused: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('without.not_copied'),
        )->said;
    }

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::what-this-stack-is-set-to');
    }

    public function answer(): WhatThisStackIsSetToTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * What a listed setting holds, to open it with, or nothing where the listing offers no control for it.
     *
     * **Refuses a key the listing did not offer a control for.** The template
     * draws the control only on a shown row, so a withheld key can only
     * arrive here from something other than the screen — and a screen that
     * trusted its own template would be one whose guarantee lives in a blade
     * file. Nothing offers to set a credential's value, and this is the half
     * of that which does not depend on markup.
     */
    protected function seedFor(string $key): ?string
    {
        foreach ($this->answer()->set as $setting) {
            if ($setting->key === $key && $setting->mayBeChanged) {
                return $setting->said;
            }
        }

        return null;
    }

    protected function mayBeChanged(string $key): bool
    {
        return $this->seedFor($key) !== null;
    }

    /** Nothing: every row here is a setting the configuration holds, and `config-set` takes each. */
    protected function keptAs(string $key): null
    {
        return null;
    }

    protected function readAgainAfterAChange(): void
    {
        $this->answered = null;
        $this->forgetWhatSetupSettled();
    }

    private function ask(): WhatThisStackIsSetToTurnedOutToBe
    {
        $this->readsItsStack();
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackIsSetToTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatThisStackIsSetToTurnedOutToBe => new HowTheSettingsRead()->signedOut(),
        );
    }

    private function asked(Stack $stack, Session $session): WhatThisStackIsSetToTurnedOutToBe
    {
        return $this->arranging->asItStands($stack, $session)->either(
            told: static fn(Settings $set, WhatWasFoundOfTheAreasLeftAlone $leftAlone): WhatThisStackIsSetToTurnedOutToBe
                => new HowTheSettingsRead()->these($set, $leftAlone),
            refused: function (Obstacle $why) use ($stack): WhatThisStackIsSetToTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheSettingsRead()->met($why);
            },
        );
    }
}
