<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\View\View;

use function in_array;
use function is_string;
use function mb_strtoupper;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ACommandToHand;
use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\AskingWhatSetupDecided;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Safekeeping;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingWhatServicesRead;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatKeepsRunning;
use Modules\Kernel\Api\WhatSetupDecided as TheDecisions;
use Modules\Kernel\Api\WhatWasFoundOfTheCredentials;
use Modules\Kernel\Api\WhatWasFoundOfTheSettings;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowWhatSetupDecidedReads;
use Modules\Operator\Internal\ProposesAChange;
use Modules\Operator\Internal\ViewModels\ADecisionAsShown;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatSetupDecidedAsShown;
use Modules\Operator\Internal\WhatTheSheetSaid;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\ReadsAStackOnceAFrame;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/** Every decision setup makes on a stack, held or not, to the operator alone, each changed through the stack's review or named as changed at the machine. */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatSetupDecided extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use ReadsAStackOnceAFrame;
    use ProposesAChange;

    private const string COPY_NAMED = 'decided.copy_named';

    public ?WhatSetupDecidedAsShown $answered = null;

    /** Every decision the stack lists, once read. */
    public ?TheDecisions $decided = null;

    /** What the settings read found, once read, on a frame after the decisions. */
    public ?WhatWasFoundOfTheSettings $stored = null;

    /** What the credentials read found, once read, on a frame after the settings. */
    public ?WhatWasFoundOfTheCredentials $safeguarded = null;

    /** The name typed into the field for any other setting. */
    public ?string $named = null;

    /** The name that field was refused, where it was. */
    public ?string $refusedName = null;

    /** The agreed change whose review opened what is still to be done, where one did. */
    public ?WhatAChangeTurnedOutToBe $opened = null;

    /** The catalogue key for what handing a command over came to, where one has been. */
    public ?string $copied = null;

    /** Whether the field for any other setting is open. */
    public bool $unfolded = false;

    public function __construct(
        private readonly AskingWhatSetupDecided $deciding,
        protected readonly Adjusting $adjusting,
        private readonly SettingWhatServicesRead $tuning,
        private readonly Safekeeping $safekeeping,
        private readonly Hosting $hosting,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        private readonly Sharing $sharing,
        private readonly Translator $catalogue,
    ) {}

    public function again(): void
    {
        $this->forgetTheReadings();
        $this->proposed = null;
        $this->copied = null;
    }

    /** Open the field for any other setting, or fold it away again. */
    public function turnTheFold(): void
    {
        $this->unfolded = ! $this->unfolded;
    }

    /** Open the setting named in the field for any other setting, unless the stack lists it as a credential. */
    public function another(): void
    {
        $named = trim($this->named ?? '');

        if ($named === '' || ! $this->mayBeChanged($named)) {
            $this->refusedName = $named === '' ? null : $named;
            $this->never();

            return;
        }

        $this->refusedName = null;
        $this->changing = $named;
        $this->typed = '';
        $this->proposed = null;
    }

    /** Hand one command the screen names to the device's own sharing, to copy out. */
    public function copy(string $command): void
    {
        if (! in_array($command, $this->answer()->commands(), strict: true)) {
            return;
        }

        $named = $this->catalogue->get(self::COPY_NAMED);

        $this->copied = $this->sharing->offerToCopy(ACommandToHand::of(TheCommandLine::of($command), is_string($named) ? $named : self::COPY_NAMED))->either(
            over: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('decided.copied'),
            refused: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('decided.not_copied'),
        )->said;
    }

    /** Whether the setting open for changing is one the field for any other setting opened. */
    public function opensFromTheField(): bool
    {
        return $this->changing() !== '' && ! $this->answer()->offered($this->changing()) instanceof ADecisionAsShown;
    }

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::what-setup-decided');
    }

    /** Every decision as shown, read a reading a frame, and saying it is reading until all four are in. */
    public function answer(): WhatSetupDecidedAsShown
    {
        $answer = $this->answered ?? $this->ask();
        $this->answered = $answer->waits ? null : $answer;

        return $answer;
    }

    protected function seedFor(string $key): ?string
    {
        $row = $this->answer()->offered($key);

        if (! $row instanceof ADecisionAsShown || ! $this->mayBeChanged($key) || ! $this->mayBeChanged($row->setting ?? $key)) {
            return null;
        }

        return $row->holds;
    }

    protected function mayBeChanged(string $key): bool
    {
        return ! $this->answer()->waits && ! in_array(mb_strtoupper(trim($key)), $this->answer()->refused, strict: true);
    }

    protected function keptAs(string $key): ?string
    {
        return $this->answer()->offered($key)?->setting;
    }

    protected function readAgainAfterAChange(): void
    {
        $proposed = $this->proposed;
        $this->opened = $proposed instanceof WhatAChangeTurnedOutToBe && $proposed->wroteSomething && $proposed->comesTo->opens !== [] ? $proposed : $this->opened;
        $this->forgetTheReadings();
    }

    private function forgetTheReadings(): void
    {
        $this->answered = null;
        $this->decided = null;
        $this->stored = null;
        $this->safeguarded = null;
    }

    private function ask(): WhatSetupDecidedAsShown
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatSetupDecidedAsShown => $this->asked($stack, $session),
            notHeld: static fn(): WhatSetupDecidedAsShown => HowWhatSetupDecidedReads::signedOut(),
        );
    }

    private function asked(Stack $stack, Session $session): WhatSetupDecidedAsShown
    {
        $decided = $this->decided;

        if ($decided instanceof TheDecisions) {
            return $this->beside($decided, $stack, $session);
        }

        $this->readsItsStack();

        return $this->deciding->decidedOn($stack, $session)->either(
            found: function (TheDecisions $found) use ($stack, $session): WhatSetupDecidedAsShown {
                $this->decided = $found;

                return $this->beside($found, $stack, $session);
            },
            met: function (Obstacle $why) use ($stack): WhatSetupDecidedAsShown {
                $this->letGoOfTheSession($why, $stack);

                return HowWhatSetupDecidedReads::met($why);
            },
        );
    }

    /** The decisions beside the settings, the credentials and the hosting, each read on a frame of its own; the hosting is read last, so the answer it completes is the one kept. */
    private function beside(TheDecisions $decided, Stack $stack, Session $session): WhatSetupDecidedAsShown
    {
        $this->stored ??= $this->onAFrameOfItsOwn(fn(): WhatWasFoundOfTheSettings => $this->tuning->readOn($stack, $session));
        $this->safeguarded ??= $this->onAFrameOfItsOwn(fn(): WhatWasFoundOfTheCredentials => $this->safekeeping->heldOn($stack, $session));

        if (! $this->stored instanceof WhatWasFoundOfTheSettings || ! $this->safeguarded instanceof WhatWasFoundOfTheCredentials) {
            return HowWhatSetupDecidedReads::waits();
        }

        $hosted = $this->onAFrameOfItsOwn(fn(): WhatKeepsRunning => $this->hosting->keptRunningOn($stack, $session));

        if (! $hosted instanceof WhatKeepsRunning) {
            return HowWhatSetupDecidedReads::waits();
        }

        return HowWhatSetupDecidedReads::of($decided, $this->stored, $this->safeguarded, $this->goes())->shown($hosted, $this->opened);
    }

    /**
     * What a reading came to, where this frame has not read the stack yet, or nothing until a frame that has not.
     *
     * @template T of object
     *
     * @param Closure(): T $reading
     *
     * @return T|null
     */
    private function onAFrameOfItsOwn(Closure $reading): ?object
    {
        return $this->mayReadItsStack() ? $reading() : null;
    }
}
