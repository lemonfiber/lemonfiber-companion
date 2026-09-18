<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\StackId;

/**
 * Where one stack's screens live, which is the only place that knows.
 *
 * `HowThisStackIs::signInAt()` said the URI was built in PHP so it would have
 * one spelling. That was the right idea and it stopped half way: the spelling
 * left the templates and landed in six classes, each building
 * `/stacks/%s/sign-in` for itself, while the provider declared a seventh. A
 * rename would have had to be found in all of them, and the one that was missed
 * would be a button leading nowhere — which is the failure the original move
 * was made to prevent.
 *
 * The paths themselves are {@see AStacksScreen}'s, which the provider registers
 * from. This is the half that knows *which machine*; that one knows *which
 * screen*, and neither can be renamed without the other following.
 *
 * **One accessor per screen rather than one per destination on each screen.**
 * `HowThisStackIs` is the screen every other is reached from, so each new
 * destination arrived on it as another `somethingAreAt()` — three of them, on
 * the way to the twenty-method ceiling `Q-R64` refuses. Handing out one of
 * these instead means the next destination costs no method at all.
 *
 * **It holds the stored identifier rather than a {@see StackId}.** Two screens
 * reach here holding only what a pairing wrote down, before anything has looked
 * it up — and a type that refused them would have those two building the URI by
 * hand, which is the situation this exists to end.
 *
 * `Internal` because where a screen lives is a detail of this module's own
 * surface; `E2`'s promise is that it can be renamed without reading another.
 */
final readonly class WhereAStackIs
{
    private function __construct(private string $stored) {}

    /** A stack this device holds. */
    public static function of(StackId $stack): self
    {
        return new self($stack->stored());
    }

    /**
     * A stack named by what a pairing wrote down.
     *
     * For the two screens that finish a pairing and send the operator onwards
     * before anything has read the stack back. They hold the identifier and
     * nothing else, which is enough to say where to go.
     */
    public static function rememberedAs(string $stored): self
    {
        return new self($stored);
    }

    /** How this machine is doing, which is what the app is for. */
    public function health(): string
    {
        return AStacksScreen::Health->forTheStack($this->stored);
    }

    /** Where a password is offered, and where a session that ended is renewed. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stored);
    }

    /** What the household has asked this machine for (`N2-R11`). */
    public function requests(): string
    {
        return AStacksScreen::Requests->forTheStack($this->stored);
    }

    /** What this machine would put right, stated before any yes (`N2-R4`). */
    public function repairs(): string
    {
        return AStacksScreen::Repairs->forTheStack($this->stored);
    }

    /** What has stopped coming in to this machine (`N2-R9`). */
    public function stuck(): string
    {
        return AStacksScreen::Stuck->forTheStack($this->stored);
    }

    /** What this machine is running, and the verbs about it (`N2-R7`). */
    public function services(): string
    {
        return AStacksScreen::Services->forTheStack($this->stored);
    }

    /** Where this machine stands on being up to date (`N2-R15`). */
    public function updates(): string
    {
        return AStacksScreen::Updates->forTheStack($this->stored);
    }

    /** What is running here that this machine never declared (`N2-R21`). */
    public function elsewhere(): string
    {
        return AStacksScreen::Elsewhere->forTheStack($this->stored);
    }

    /** What one of this machine's services has been saying (`N2-R10`). */
    public function logsOf(ServiceId $service): string
    {
        return AStacksScreen::Logs->forTheStacksService($this->stored, $service->named());
    }

    /** One service of this machine, and the verbs about it (`N2-R7`). */
    public function doingWith(ServiceId $service): string
    {
        return AStacksScreen::Doing->forTheStacksService($this->stored, $service->named());
    }

    /**
     * One whole form of this machine, and the verbs about it (`N2-R7`).
     *
     * The same screen as the one above, because what an operator is choosing
     * between is identical and only the name the stack is told differs.
     *
     * Text rather than a {@see Form}, which is the one place on this class it
     * is. A form reaches a screen as the name the stack sent — the listing
     * carries `list<string>` and the verb has always been asked for by that
     * name — and {@see Form::called()} refuses a blank, so building the value
     * to make a route would put a raise on a tap. The screen refuses a name it
     * never read, which is where that refusal belongs.
     */
    public function doingWithTheForm(string $named): string
    {
        return AStacksScreen::Doing->forTheStacksService($this->stored, $named);
    }
}
