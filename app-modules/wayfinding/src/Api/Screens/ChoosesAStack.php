<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api\Screens;

use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\StackId;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Modules\Wayfinding\Api\TheStacksToChooseFrom;
use Modules\Wayfinding\Api\WhatEachStackSaidSoFar;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * The list of stacks the name in the top bar opens, on a screen about a stack.
 *
 * The list is a sheet drawn over the screen while it is open. Choosing from it
 * closes it and opens where the choice leads in the same press: a sheet left
 * open over the screen it opened is what the operator would see first on
 * coming back.
 *
 * **It listens while it is open**, by the list of stacks' own rules, so each
 * row says how its stack stands now rather than a word that has gone out of
 * date. Its first frame is drawn from what was kept, and every way it closes
 * lets go. Whether the screen already holds its own stack's stream it says
 * through {@see holdsItsStacksStream()}, and that stack is then not asked
 * twice; the screen's own `stop()` lets the list go with everything else it
 * holds, through {@see letTheListOfStacksGo()}.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ChoosesAStack
{
    /** Whether the list of stacks is open over this screen. */
    public bool $choosingAStack = false;

    /**
     * What the list has heard from each stack while it was open.
     *
     * `public` because it is what the component's property syncing writes.
     */
    public ?WhatEachStackSaidSoFar $heardWhileChoosing = null;

    public function chooseAStack(): void
    {
        $this->choosingAStack = true;
    }

    public function stopChoosingAStack(): void
    {
        $this->choosingAStack = false;
        $this->letTheListOfStacksGo();
    }

    /**
     * Take what each stack's subscription has delivered while the list is open.
     *
     * Nothing while it is shut, so a screen with the list closed reaches no stack.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_LISTENING_MS)]
    public function hearEachStackWhileChoosing(): void
    {
        if (! $this->choosingAStack) {
            return;
        }

        $this->heardWhileChoosing = $this->around->heardWhileChoosing(
            $this->heardWhileChoosingSoFar(),
            $this->stack(),
            $this->holdsItsStacksStream(),
        );
    }

    /** Open the stack this phone calls `$id`, where choosing it leads, which lets go as the screen stops. */
    public function openTheStack(string $id): void
    {
        $this->choosingAStack = false;
        $this->navigate($this->around->choosingLeadsTo($this->around->stack(StackId::rememberedAs($id))));
    }

    /** Begin pairing another stack, which lets go as the screen stops. */
    public function addAStack(): void
    {
        $this->choosingAStack = false;
        $this->navigate(AScreenWithoutAStack::PairByScanning->value);
    }

    /**
     * The stacks to choose from, read only while the list is open, so a frame
     * with the list closed asks nothing of what the phone kept.
     */
    public function stacksToChooseFrom(): TheStacksToChooseFrom
    {
        return $this->choosingAStack ? $this->around->stacksToChooseFrom($this->stack()) : TheStacksToChooseFrom::none();
    }

    /** Whether this screen holds its own stack's stream, which the list then does not ask for. */
    abstract protected function holdsItsStacksStream(): bool;

    /**
     * Every subscription the list holds let go of, and what each held kept as no longer current.
     *
     * The screen calls it from its own `stop()`, whenever it stops being the
     * one in front. The list stays open, and a return opens its subscriptions
     * again at once.
     */
    private function letTheListOfStacksGo(): void
    {
        $this->heardWhileChoosing = $this->around->stopHearingWhileChoosing($this->heardWhileChoosingSoFar());
    }

    private function heardWhileChoosingSoFar(): WhatEachStackSaidSoFar
    {
        return $this->heardWhileChoosing ??= WhatEachStackSaidSoFar::nothingYet();
    }
}
