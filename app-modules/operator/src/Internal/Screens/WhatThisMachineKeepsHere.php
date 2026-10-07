<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Copying;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Storing;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheCopies;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatThisMachineKeeps;
use Modules\Operator\Internal\LooksAgainWhileOpen;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowWhatIsKeptReads;
use Modules\Operator\Internal\ReadsAStackOnceAFrame;
use Modules\Operator\Internal\ViewModels\TheCopiesAsFound;
use Modules\Operator\Internal\ViewModels\WhatIsKeptTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine keeps, where, and why, and the copies of the stack it holds.
 *
 * Each thing kept is shown with where it is and why the stack keeps it. Whether
 * it holds a secret is said, and the secret is not shown: nothing this screen
 * reads carries a value. What is on the machine and is not the stack's is
 * listed beside it.
 *
 * **Two readings, one a frame.** What the stack keeps is asked first, and an
 * obstacle there is the screen's obstacle, as on every other screen. A frame
 * reads a stack once, so the copies are asked on the frame after, and the
 * first frame says they are being read and asks for the next one at once.
 * A list that could not be read is drawn as its own sentence, never as an
 * empty list.
 *
 * **It reads and changes nothing.** Taking a copy and putting one back each
 * open a screen of their own, {@see TakingACopyHere} and
 * {@see PuttingACopyBack}, which say what they would do before anything is
 * agreed to. Putting back is offered only for a copy the stack listed.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatThisMachineKeepsHere extends NativeComponent
{
    use AsksTheStackAgain;
    use LooksAgainWhileOpen;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use ReadsAStackOnceAFrame;

    /**
     * What came back, once the frame has asked.
     *
     * `public` for {@see HowTheLineIsSharedHere::$answered}'s reason.
     */
    public ?WhatIsKeptTurnedOutToBe $answered = null;

    /** The copies, once a frame has asked for them. `public` for the same reason. */
    public ?TheCopiesAsFound $copiesFound = null;

    public function __construct(
        private readonly Storing $storing,
        private readonly Copying $copying,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Ask the machine again, both readings. */
    public function again(): void
    {
        $this->answered = null;
        $this->copiesFound = null;
    }

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::what-this-machine-keeps-here');
    }

    /** What the machine keeps, asked once and held. */
    public function answer(): WhatIsKeptTurnedOutToBe
    {
        if (! $this->answered instanceof WhatIsKeptTurnedOutToBe) {
            $this->readsItsStack();
            $this->answered = $this->ask();
        }

        return $this->answered;
    }

    /** Whether the copies wait for the next frame, because this one has read the stack already. */
    public function copiesAreBeingRead(): bool
    {
        return ! $this->copies() instanceof TheCopiesAsFound;
    }

    /**
     * The copies the machine holds, or nothing while they wait for a frame of their own.
     *
     * Asked only once what the machine keeps has come back, and only on a frame
     * that has not read the stack already.
     */
    public function copies(): ?TheCopiesAsFound
    {
        if ($this->copiesFound instanceof TheCopiesAsFound || ! $this->mayReadItsStack()) {
            return $this->copiesFound;
        }

        $stack = $this->stack();

        return $this->copiesFound = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheCopiesAsFound => $this->copiesOn($stack, $session),
            notHeld: static fn(): TheCopiesAsFound => new HowWhatIsKeptReads()->copiesSignedOut(),
        );
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): WhatIsKeptTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatIsKeptTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatIsKeptTurnedOutToBe => new HowWhatIsKeptReads()->signedOut(),
        );
    }

    /** What the machine said it keeps, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatIsKeptTurnedOutToBe
    {
        return $this->storing->storedOn($stack, $session)->either(
            kept: static fn(WhatThisMachineKeeps $keeps): WhatIsKeptTurnedOutToBe
                => new HowWhatIsKeptReads()->this($keeps),
            met: $this->lettingGoIfRefused($stack, new HowWhatIsKeptReads()->met(...)),
        );
    }

    /** The copies the machine holds, or what stopped them being listed. */
    private function copiesOn(Stack $stack, Session $session): TheCopiesAsFound
    {
        return $this->copying->copiesOn($stack, $session)->either(
            copies: static fn(TheCopies $copies): TheCopiesAsFound => new HowWhatIsKeptReads()->copies($copies),
            met: $this->lettingGoIfRefused($stack, new HowWhatIsKeptReads()->copiesMet(...)),
        );
    }
}
