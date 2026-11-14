<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function array_key_exists;

use Closure;
use Illuminate\View\View;
use Modules\Connection\Api\ChoosingWhatReachesThePhone;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Connection\Api\TurningNotificationsOn;
use Modules\Design\Api\TakesTheThemeItOpensOver;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\KindOfNotification;
use Modules\Kernel\Api\KnowingWhatAStackOffers;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Telling;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheReadingWaitsAFrame;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatMayReachThePhone;
use Modules\Kernel\Api\WhatTheOperatorIsTold;
use Modules\Operator\Internal\ChoosesNotifications;
use Modules\Operator\Internal\HasAWayBack;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowWhatAStackMayTellReads;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatAStackMayTellAsShown;
use Modules\Wayfinding\Api\Screens\ReadsAStackOnceAFrame;
use Modules\Wayfinding\Api\WhoTheSettingsSpeakTo;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * The notification page: the phone's switch, and a section for each stack saying what of it may reach the phone.
 *
 * Each stack is asked which kinds of notification it raises, and a switch is
 * drawn for each of those and for no other; where it raises problems, the
 * lowest severity of them told is offered under them. What the operator
 * chooses is kept on the phone as it is made and never sent to the stack.
 *
 * **One stack a frame**, in the operator's order: a frame reads a stack once,
 * so a frame that has read one asks for the next at once while another is
 * still to be read, and a stack that had to be asked what it serves waits its
 * turn the same way. What each stack raises is held while the page is open,
 * so a choice redraws its section without asking the stack again. A session
 * the stack refuses is let go of.
 *
 * `Concealed` for the reason every screen reading a stack over its session is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatEachStackMayTellThisPhone extends NativeComponent implements TakesTheThemeItOpensOver
{
    use WritesItsTaps;
    use HasAWayBack;
    use ChoosesNotifications;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use ReadsAStackOnceAFrame;

    /** Whose words the switch speaks in: the operator's, as this page is theirs alone. */
    public WhoTheSettingsSpeakTo $speaksTo = WhoTheSettingsSpeakTo::Anyone;

    /**
     * Each stack's section read so far, by the stack's stored id, held while the page is open.
     *
     * @var array<string, WhatAStackMayTellAsShown>
     */
    public array $read = [];

    /**
     * What this frame draws, worked out once however often the template asks.
     *
     * @var list<WhatAStackMayTellAsShown>|null
     */
    private ?array $shown = null;

    public function __construct(
        private readonly Stacks $stacks,
        private readonly SecureStorage $storage,
        private readonly Telling $telling,
        private readonly ChoosingWhatReachesThePhone $choosing,
        private readonly KnowingWhatAStackOffers $offering,
        protected readonly TurningNotificationsOn $notifications,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** The page opens, and what each stack offers is asked again where it was asked long ago. */
    public function mount(): void
    {
        $this->offering->aScreenOpens();
    }

    /**
     * Each stack's section read so far, in the operator's order, reading the first that has not been.
     *
     * @return list<WhatAStackMayTellAsShown>
     */
    public function sections(): array
    {
        if ($this->shown !== null) {
            return $this->shown;
        }

        $shown = [];

        foreach ($this->stacks->configured() as $stack) {
            $key = $stack->id()->stored();

            if (! array_key_exists($key, $this->read) && $this->mayReadItsStack()) {
                $this->read($stack);
            }

            if (array_key_exists($key, $this->read)) {
                $shown[] = $this->read[$key];
            }
        }

        return $this->shown = $shown;
    }

    /** A kind's switch was turned on or off for a stack, taken as the state the switch now shows. */
    public function kindTurned(string $stack, string $kind, bool $on): void
    {
        $turned = KindOfNotification::tryFrom($kind);

        if ($turned instanceof KindOfNotification) {
            $this->chose($stack, fn(StackId $id): WhatMayReachThePhone => $on ? $this->choosing->switchOn($id, $turned) : $this->choosing->switchOff($id, $turned));
        }
    }

    /** The operator chose the lowest severity of problem told from a stack. */
    public function problemsFrom(string $stack, string $severity): void
    {
        $floor = Severity::tryFrom($severity);

        if ($floor instanceof Severity) {
            $this->chose($stack, fn(StackId $id): WhatMayReachThePhone => $this->choosing->tellProblemsFrom($id, $floor));
        }
    }

    public function render(): View
    {
        $this->aFrameBegins();
        $this->shown = null;

        return view('operator::what-each-stack-may-tell-this-phone');
    }

    /**
     * Keep a choice for a stack this page drew, and draw its section again.
     *
     * @param Closure(StackId): WhatMayReachThePhone $choosing
     */
    private function chose(string $stack, Closure $choosing): void
    {
        if (array_key_exists($stack, $this->read)) {
            $this->read[$stack] = new HowWhatAStackMayTellReads()->again($this->read[$stack], $choosing(StackId::rememberedAs($stack)));
            $this->shown = null;
        }
    }

    /** Read one stack's section, or leave it for the next frame where this one had to ask what it serves. */
    private function read(Stack $stack): void
    {
        try {
            $this->read[$stack->id()->stored()] = $this->readNow($stack);
        } catch (TheReadingWaitsAFrame) {
            $this->waitsThisFrame = true;
        }
    }

    /** One stack's section: what it raises, or what stood in the way. */
    private function readNow(Stack $stack): WhatAStackMayTellAsShown
    {
        $key = $stack->id()->stored();
        $name = $stack->name()->shown();
        $chosen = $this->choosing->forTheStack($stack->id());

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAStackMayTellAsShown => $this->telling->toldAbout($stack, $session)->either(
                told: static fn(WhatTheOperatorIsTold $told): WhatAStackMayTellAsShown => new HowWhatAStackMayTellReads()->told($key, $name, $told->kinds(), $chosen),
                met: $this->lettingGoIfRefused($stack, static fn(Obstacle $why): WhatAStackMayTellAsShown => new HowWhatAStackMayTellReads()->unread($key, $name, HowTheReadingWent::somethingStopped($why), $chosen)),
            ),
            notHeld: static fn(): WhatAStackMayTellAsShown => new HowWhatAStackMayTellReads()->unread($key, $name, HowTheReadingWent::theSessionEnded(), $chosen),
        );
    }
}
