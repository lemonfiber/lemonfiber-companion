<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Tracing;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhereItGotTo;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowATraceReads;
use Modules\Operator\Internal\ShowsWhatItsWordsMean;
use Modules\Operator\Internal\ViewModels\TheTraceTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Where one item got to: the answer to *where is my show?*.
 *
 * Opened on what the route names, and able to follow anything else typed into
 * it. How sure the trace is comes first, since a guess drawn as fact is
 * worse than a marked one; then how far it got and why it stopped, what has
 * been tried, where the services disagree, and for a series what is here and
 * outstanding season by season. Nothing asked for is its own answer.
 *
 * This is the operator's trace. It carries the pipeline's internals and is
 * not a member's view of their own request.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhereThisGotTo extends NativeComponent
{
    use LetsGoOfARefusedSession;
    use ShowsWhatItsWordsMean;

    /** What was typed and followed instead of what the route names, or empty. */
    public string $following = '';

    /** What is typed into the box, followed only when asked to. Public for {@see WhatThisServiceSaid::$looking}'s reason. */
    public string $looking = '';

    /** What came back, once the frame has asked. */
    public ?TheTraceTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Tracing $tracing,
        private readonly Explaining $explaining,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /** The stack this screen is about, read from the route on every frame, for {@see WhatStoppedComingIn::stack()}'s reason. */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Follow what was typed instead; nothing typed follows nothing new. */
    public function follow(): void
    {
        if (trim($this->looking) === '') {
            return;
        }

        $this->following = trim($this->looking);
        $this->looking = '';
        $this->answered = null;
    }

    /** Ask the machine again. */
    public function again(): void
    {
        $this->answered = null;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::where-this-got-to', ['looking' => $this->looking]);
    }

    /** What came back, asked once per frame. */
    public function answer(): TheTraceTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Where this screen's words are explained from. */
    protected function explaining(): Explaining
    {
        return $this->explaining;
    }

    /** Resume the session, follow the item, and flatten what came back. */
    private function ask(): TheTraceTurnedOutToBe
    {
        $term = $this->term();

        if ($term === '') {
            return new HowATraceReads()->nothingToFollow();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheTraceTurnedOutToBe => $this->asked($stack, $session, WhatToFollow::called($term)),
            notHeld: static fn(): TheTraceTurnedOutToBe => new HowATraceReads()->signedOut($term),
        );
    }

    /**
     * What is followed: what was typed and followed, or else what the route names.
     *
     * Read from the route on every frame, for {@see Stack()}'s reason.
     */
    private function term(): string
    {
        if ($this->following !== '') {
            return $this->following;
        }

        $named = $this->param('service');

        return is_string($named) ? trim($named) : '';
    }

    /** Where the item got to, or what the operator met instead. */
    private function asked(Stack $stack, Session $session, WhatToFollow $following): TheTraceTurnedOutToBe
    {
        return $this->tracing->tracedOn($stack, $session, $following)->either(
            found: static fn(WhereItGotTo $trace): TheTraceTurnedOutToBe => new HowATraceReads()->this($trace),
            met: function (Obstacle $why) use ($stack, $following): TheTraceTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowATraceReads()->met($why, $following->term());
            },
        );
    }
}
