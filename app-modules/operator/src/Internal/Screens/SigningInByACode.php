<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Connection\Api\RememberingTheWayIn;
use Modules\Connection\Api\TheWayIn;
use Modules\Kernel\Api\ACodeToApprove;
use Modules\Kernel\Api\AdmittingByACode;
use Modules\Kernel\Api\Assembled;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Encoding;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\LocalZone;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OpeningAPage;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhenToTryAgain;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhySessionCannotBeKept;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowACodeReads;
use Modules\Operator\Internal\Presenters\HowTheWaitReads;
use Modules\Operator\Internal\ViewModels\TheCodeAsShown;
use Modules\Operator\Internal\ViewModels\TheWaitAsShown;
use Modules\Operator\Internal\WhatTheSheetSaid;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Operator\Internal\WhichSurfaceTheyAreGiven;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Signing a member in by a code a service outside the house approves.
 *
 * The code is asked for as the screen is first drawn, and the house is asked whether it
 * was approved every few seconds while the screen is shown, and only then. The
 * handle that finishes the sign-in stays in this screen's memory: it is never
 * a public property, stored, logged or put in an address.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class SigningInByACode extends NativeComponent
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::signing-in-by-a-code';

    /** What the code is called where it is handed to the phone's sharing. */
    private const string THE_CODE_IS_CALLED = 'connection.code_title';

    /** What stood in the way of asking or finishing, once something did. */
    public HowTheSignInWent $went = HowTheSignInWent::NotYet;

    /** The house's own words where it no longer waits on the code, or signs in by password. */
    public ?WhatTheHouseholdWasTold $told = null;

    /** Whether the house said it signs its members in by password. */
    public bool $byPassword = false;

    /** How long the house said to wait before asking again. */
    public ?TheWaitAsShown $wait = null;

    /** Whether the phone would not open the page to approve the code at. */
    public bool $pageWouldNotOpen = false;

    /** What the phone's sharing said when the code was offered to copy, once it was. */
    public ?string $copied = null;

    /** The code the house issued, with the handle that finishes it, held in memory only. */
    private ?ACodeToApprove $asked = null;

    /** The code as it is drawn, worked out once a code is issued. */
    private ?TheCodeAsShown $shown = null;

    /** Whether a code was asked for yet, which the first frame does. */
    private bool $hasAsked = false;

    public function __construct(
        private readonly AdmittingByACode $admitting,
        private readonly SecureStorage $storage,
        private readonly RememberingTheWayIn $waysIn,
        private readonly OpeningAPage $pages,
        private readonly Sharing $sharing,
        private readonly Encoding $encoding,
        private readonly LocalZone $here,
        private readonly Translator $catalogue,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Ask the house for a code, in place of any code shown before. */
    public function askForACode(): void
    {
        $this->asked = null;
        $this->shown = null;
        $this->told = null;
        $this->byPassword = false;
        $this->wait = null;
        $this->pageWouldNotOpen = false;
        $this->copied = null;
        $this->went = HowTheSignInWent::NotYet;
        $this->hasAsked = true;

        $this->admitting->askForACode($this->stack())->either(
            issued: function (ACodeToApprove $code): self {
                $this->asked = $code;

                return $this;
            },
            byPassword: function (WhatTheHouseholdWasTold $told): self {
                $this->told = $told;
                $this->byPassword = true;

                return $this;
            },
            refused: $this->met(...),
        );
    }

    /** Ask the house whether the code shown was approved. */
    #[Poll(HowOftenAScreenLooks::WHILE_A_CODE_WAITS_MS)]
    public function whileTheCodeWaits(): void
    {
        $asked = $this->asked;

        if (! $asked instanceof ACodeToApprove) {
            return;
        }

        $this->admitting->finishByCode($this->stack(), $asked->waiting)->either(
            opened: fn(Session $session, Instant $until, Whose $whose): self => $this->in($session, $whose),
            waiting: function (): self {
                $this->went = HowTheSignInWent::NotYet;
                $this->wait = null;

                return $this;
            },
            lapsed: function (WhatTheHouseholdWasTold $told): self {
                $this->asked = null;
                $this->shown = null;
                $this->told = $told;

                return $this;
            },
            refused: $this->met(...),
        );
    }

    /** The code as it is drawn, or nothing while no code is shown; the first frame asks the house for one. */
    public function theCode(): ?TheCodeAsShown
    {
        if (! $this->hasAsked) {
            $this->askForACode();
        }

        $asked = $this->asked;

        if (! $asked instanceof ACodeToApprove) {
            return null;
        }

        return $this->shown ??= new TheCodeAsShown(
            $asked->code,
            $this->here->zone()->timeOfDayAt($asked->lapses)->shownToTheMinute(),
            new HowACodeReads()->squares($this->encoding->codeFor($asked->approveAt)),
        );
    }

    /** Open the page the code is approved at in the phone's browser. */
    public function openThePage(): void
    {
        $asked = $this->asked;

        if ($asked instanceof ACodeToApprove) {
            $this->pageWouldNotOpen = $this->pages->open($asked->approveAt)->wouldNotOpen();
        }
    }

    /** Offer the code to copy, through the phone's own sharing. */
    public function copyTheCode(): void
    {
        $asked = $this->asked;

        if ($asked instanceof ACodeToApprove) {
            $called = $this->catalogue->get(self::THE_CODE_IS_CALLED);

            $this->copied = $this->sharing->hand(Assembled::as(is_string($called) ? $called : self::THE_CODE_IS_CALLED, $asked->code))->either(
                over: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('connection.code_copied'),
                refused: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('connection.code_not_copied'),
            )->said;
        }
    }

    /** Go back to signing in with a password. */
    public function usePassword(): void
    {
        $this->back();
    }

    /**
     * Keep the session the house opened, note that a code let a member in on it, and go on to what they came for.
     */
    private function in(Session $session, Whose $whose): self
    {
        $stack = $this->stack()->id();
        $this->went = $this->storage->keep($stack, $session, $whose)->either(
            kept: static fn(): HowTheSignInWent => HowTheSignInWent::SignedIn,
            refused: static fn(WhySessionCannotBeKept $why): HowTheSignInWent => HowTheSignInWent::unkept($why),
        );

        if (! $this->went->isSignedIn()) {
            return $this;
        }

        $this->asked = null;
        $this->shown = null;
        $this->waysIn->letIn($stack, TheWayIn::Code);

        $given = $whose->either(
            operator: static fn(): WhichSurfaceTheyAreGiven => WhichSurfaceTheyAreGiven::TheReport,
            member: static fn(): WhichSurfaceTheyAreGiven => WhichSurfaceTheyAreGiven::TheirHome,
        );

        $this->replaceTheWholeStack(match ($given) {
            WhichSurfaceTheyAreGiven::TheReport => WhereAStackIs::of($stack)->to(AStacksScreen::Health),
            WhichSurfaceTheyAreGiven::TheirHome => AStacksScreen::Shelf->forTheStack($stack),
        });

        return $this;
    }

    /** What stood in the way, and how long the house said to wait. */
    private function met(Obstacle $why, WhenToTryAgain $tryAgain): self
    {
        $this->went = HowTheSignInWent::met($why);
        $this->wait = new HowTheWaitReads()->of($tryAgain);

        return $this;
    }
}
