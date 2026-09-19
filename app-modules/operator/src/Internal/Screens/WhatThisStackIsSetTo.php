<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheSettingsRead;
use Modules\Operator\Internal\ViewModels\WhatThisStackIsSetToTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
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
 * **{@see Concealed} because a withheld row is still about a credential.** The
 * stack withholds the value and sends a note in its place, so nothing secret
 * reaches this screen — but the *names* do, and a task-switcher snapshot of a
 * list of credential keys is a list of what this household holds and where.
 */
#[Lazy]
#[Concealed]
final class WhatThisStackIsSetTo extends NativeComponent
{
    use LetsGoOfARefusedSession;

    protected ?WhatThisStackIsSetToTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Arranging $arranging,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

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
    }

    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-this-stack-is-set-to');
    }

    public function answer(): WhatThisStackIsSetToTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    private function ask(): WhatThisStackIsSetToTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackIsSetToTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatThisStackIsSetToTurnedOutToBe => new HowTheSettingsRead()->signedOut(),
        );
    }

    private function asked(Stack $stack, Session $session): WhatThisStackIsSetToTurnedOutToBe
    {
        return $this->arranging->asItStands($stack, $session)->either(
            told: static fn(Settings $set): WhatThisStackIsSetToTurnedOutToBe
                => new HowTheSettingsRead()->these($set),
            refused: function (Obstacle $why) use ($stack): WhatThisStackIsSetToTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheSettingsRead()->met($why);
            },
        );
    }
}
