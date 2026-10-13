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
 * **It listens to every stack, open or shut**, by the list of stacks' own
 * rules, so each alert any stack the operator is signed in to raises is told
 * while the app runs, and each row says how its stack stands now rather than
 * a word that has gone out of date. Its first frame is drawn from what was
 * kept. Whether the screen already holds its own stack's stream it says
 * through {@see holdsItsStacksStream()}, and that stack is then not asked
 * twice; the screen's own `stop()` lets every stack go with everything else
 * it holds, through {@see letTheListOfStacksGo()}.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ChoosesAStack
{
    /** Whether the list of stacks is open over this screen. */
    public bool $choosingAStack = false;

    /**
     * What has been heard from each stack.
     *
     * `public` because it is what the component's property syncing writes.
     */
    public ?WhatEachStackSaidSoFar $heardFromEachStack = null;

    public function chooseAStack(): void
    {
        $this->choosingAStack = true;
    }

    public function stopChoosingAStack(): void
    {
        $this->choosingAStack = false;
    }

    /** Take what each stack's subscription has delivered, the list open or shut. */
    #[Poll(HowOftenAScreenLooks::WHILE_LISTENING_MS)]
    public function hearEachStack(): void
    {
        $this->heardFromEachStack = $this->around->heardFromEachStack(
            $this->heardFromEachStackSoFar(),
            $this->stack(),
            $this->holdsItsStacksStream(),
        );
    }

    /** Open the stack this phone calls `$id`, where choosing it leads. */
    public function openTheStack(string $id): void
    {
        $this->choosingAStack = false;
        $this->navigate($this->around->choosingLeadsTo($this->around->stack(StackId::rememberedAs($id))));
    }

    /** Begin pairing another stack. */
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
     * Every subscription to each stack let go of, and what each held kept as no longer current.
     *
     * The screen calls it from its own `stop()`, whenever it stops being the
     * one in front. The list stays as it was, and a return opens the
     * subscriptions again at once.
     */
    private function letTheListOfStacksGo(): void
    {
        $this->heardFromEachStack = $this->around->stopHearingEachStack($this->heardFromEachStackSoFar());
    }

    private function heardFromEachStackSoFar(): WhatEachStackSaidSoFar
    {
        return $this->heardFromEachStack ??= WhatEachStackSaidSoFar::nothingYet();
    }
}
