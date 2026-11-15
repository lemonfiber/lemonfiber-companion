<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AskingTheWatcher;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheWatcherSees;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheWatcherReads;
use Modules\Operator\Internal\ViewModels\TheWatcherAsShown;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\ReadsAStackOnceAFrame;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What the outside watcher sees: every monitor, what each covers, and what the stack says of the same service beside it.
 *
 * **Two readings, one a frame.** The watcher is the screen's own reading; the
 * stack's own reading of its services is read on the next frame and drawn
 * beside each monitor that covers one, each labelled with who said it and
 * never folded into one verdict.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class TheOutsideWatcher extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use ReadsAStackOnceAFrame;

    /** What the watcher sees, once the frame has asked. Public for {@see WhatKeepsRunningHere::$answered}'s reason. */
    public ?WhatTheWatcherSees $seen = null;

    /** What the screen drew of it last, once a frame has. Public for the same reason. */
    public ?TheWatcherAsShown $answered = null;

    /** What the stack says of its own services, once a frame has read them. Public for the same reason. */
    public ?Daemons $services = null;

    /** Whether the stack's own reading was asked and could not be had. Public for the same reason. */
    public bool $servicesUnread = false;

    public function __construct(
        private readonly AskingTheWatcher $asking,
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        private readonly Clock $clock,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Let go of both readings, so the next frames take them again. */
    public function again(): void
    {
        $this->seen = null;
        $this->answered = null;
        $this->services = null;
        $this->servicesUnread = false;
    }

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::the-outside-watcher');
    }

    /** What the watcher sees, asked once, beside the stack's own reading where a frame has read it. */
    public function answer(): TheWatcherAsShown
    {
        if (! $this->answered instanceof TheWatcherAsShown) {
            $this->readsItsStack();
            $stack = $this->stack();
            $this->answered = $this->storage->resume($stack->id())->either(
                held: fn(Session $session): TheWatcherAsShown => $this->asking->whatItSees($stack, $session)->either(
                    said: fn(WhatTheWatcherSees $sees): TheWatcherAsShown => $this->shown($this->seen = $sees),
                    met: $this->lettingGoIfRefused($stack, new HowTheWatcherReads()->met(...)),
                ),
                notHeld: static fn(): TheWatcherAsShown => new HowTheWatcherReads()->signedOut(),
            );
        }

        return $this->answered;
    }

    /** Whether the stack's own reading waits for the next frame, this one having read the watcher. */
    public function servicesAreBeingRead(): bool
    {
        $this->readServices();

        return $this->seen instanceof WhatTheWatcherSees && ! $this->services instanceof Daemons && ! $this->servicesUnread;
    }

    /** Read the stack's own services on a frame of their own, and draw the watcher again beside them. */
    private function readServices(): void
    {
        if (! $this->seen instanceof WhatTheWatcherSees || $this->services instanceof Daemons || $this->servicesUnread || ! $this->mayReadItsStack()) {
            return;
        }

        $stack = $this->stack();
        $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheWatcherAsShown => $this->supervising->running($stack, $session)->either(
                these: fn(Daemons $services): TheWatcherAsShown => $this->shown($this->seen, $this->services = $services),
                met: fn(): TheWatcherAsShown => $this->unread(),
            ),
            notHeld: fn(): TheWatcherAsShown => $this->unread(),
        );
    }

    /** The stack's own reading could not be had, so the watcher is drawn alone. */
    private function unread(): TheWatcherAsShown
    {
        $this->servicesUnread = true;

        return $this->answer();
    }

    private function shown(WhatTheWatcherSees $sees, ?Daemons $services = null): TheWatcherAsShown
    {
        return $this->answered = new HowTheWatcherReads()->this($sees, $this->clock->now(), $services);
    }
}
