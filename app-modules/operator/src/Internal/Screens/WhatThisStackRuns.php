<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\TakesItsFormsAFrameLater;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Services\Api\KeepingWhatItRuns;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine is running, and the three things to do about it.
 *
 * The app offers start, stop and restart by form and by service,
 * and this is where that is offered. A screen of its own rather than a section
 * of {@see HowThisStackIs}: health answers *is anything wrong*, and this
 * answers *what is on, and what do I want on* — which an operator opens the app
 * for on an evening when every check passes and the film still will not play.
 *
 * **The yes is built from the listing, never from the tap.** A
 * disruptive action to state what it disturbs before it is confirmed, and the
 * way that requirement is broken is never deliberate: a template draws a row,
 * the stop button is right there, and a handler passes its argument straight to
 * the port. So {@see wouldYouLike()} takes two names, finds the row they belong
 * to in what was actually read, and builds {@see AgreedTo} from *that* — a name
 * this screen never read cannot be acted on, whatever a template sends.
 *
 * **A start is not confirmed and the other two are.** That line is
 * {@see WhatToDoWithIt::takesSomethingAway()}'s and is not redrawn here. A
 * screen that asked about a start would be teaching an operator to confirm
 * without reading, which is what makes the stop confirmation worth anything.
 *
 * **It says how long a stop lasts, which it could not until recently.** The rule
 * asks for the bound the stack reported or for the fact that it reported none,
 * and for a long time no payload carried either — the gap was held by
 * `WhatTheContractDoesNotCarryTest`, which went red the day lemonfiber began
 * reporting it and named this requirement to go and answer. The sentence is in
 * the confirmation now, and the number is the stack's: this app may not
 * side inventing one, and a length worked out here would be a guess at
 * something the stack knows, wrong in exactly the cases somebody most needs it.
 *
 * That row named the `lifecycle` envelope at first, on the reasoning that a
 * bound would arrive where what an operation touched already arrives. It
 * arrived on the reading instead, because a bound is only any use *before* the
 * verb runs — which is why a register watches for a fact and not for a place.
 *
 * **It asks the stack again only while something is settling.** A service that is
 * starting becomes a running one on its own, and *ask again* as the only road
 * to finding out is the reliance on leaving and returning that rule refuses.
 * Every other state here is standing, so the cadence costs a machine on a home
 * network nothing the rest of the time — which is what keeps this from being
 * the polling that is refused.
 *
 * **It opens on what the phone kept.** The first frame draws the listing kept
 * from an earlier session, with how long ago it was read, before the stack is
 * asked anything; the fresh listing replaces it and is kept in its place.
 * Where the stack cannot be reached, the kept listing stays, beside what stood
 * in the way. Every action on a service or a form waits for a fresh listing.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and a diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatThisStackRuns extends NativeComponent
{
    use OffersTheAppsSettings;
    use TakesItsFormsAFrameLater;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * Whether what is not installed is opened out.
     *
     * Held on the screen, because it is what the operator did here and is
     * worth nothing once they leave: the fold starts closed every time.
     */
    public bool $showsWhatIsNotInstalled = false;

    public function __construct(
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        private readonly KeepingWhatItRuns $kept,
    ) {}

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the whole fold rather than one per field, which
     * is what keeps this screen under `H3`'s twenty methods. The asking itself
     * is {@see TakesItsFormsAFrameLater}'s, and is handed what it needs.
     */
    public function answer(): WhatThisStackRunsTurnedOutToBe
    {
        return $this->listingOf($this->stack(), $this->storage, $this->supervising, $this->kept);
    }

    /** The forms the stack declares, or nothing while they wait for the next frame. */
    public function forms(): ?TheFormsAsFound
    {
        return $this->formsOf($this->stack(), $this->storage, $this->supervising, $this->kept);
    }


    /**
     * Look again while the machine is settling into what it was told.
     *
     * It does nothing unless something is actually settling, which is what
     * keeps this from being the polling that is refused: a stack whose
     * services are all in standing states answers the same thing however often
     * it is read.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItSettles(): void
    {
        if (! $this->answer()->isSettling) {
            return;
        }

        $this->again();
    }

    /** Open what is not installed out, or fold it away again. */
    public function showWhatIsNotInstalled(): void
    {
        $this->showsWhatIsNotInstalled = ! $this->showsWhatIsNotInstalled;
    }


    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::what-this-stack-runs');
    }

    /**
     * Open the stack's stream and ask the stack, behind the first frame.
     *
     * The first frame drew what the phone kept; the next frame asks, and what
     * the stack says then replaces it.
     */
    public function mount(): void
    {
        $this->listen();
        $this->answered = null;
    }

    /**
     * The first frame: the listing the phone kept, drawn as the screen draws
     * any, with its age, before the stack is asked anything.
     *
     * Only where nothing was kept is the frame the platform's indicator.
     */
    protected function placeholder(): Element|View
    {
        return $this->opensOnWhatWasKept($this->stack(), $this->kept)
            ? view('operator::what-this-stack-runs')
            : parent::placeholder();
    }
}
