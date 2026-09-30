<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Operator\Internal\AScreenWithoutAStack;
use Modules\Operator\Internal\ViewModels\AStackToChooseAsShown;

/**
 * The list of stacks the name in the top bar opens, on a screen about a stack.
 *
 * The list is a sheet drawn over the screen while it is open. Choosing from it
 * closes it and opens where the choice leads in the same press: a sheet left
 * open over the screen it opened is what the operator would see first on
 * coming back.
 */
trait ChoosesAStack
{
    /** Whether the list of stacks is open over this screen. */
    public bool $choosingAStack = false;

    public function chooseAStack(): void
    {
        $this->choosingAStack = true;
    }

    public function stopChoosingAStack(): void
    {
        $this->choosingAStack = false;
    }

    /** Open the stack this phone calls `$id`, where choosing it leads. */
    public function openTheStack(string $id): void
    {
        $this->choosingAStack = false;
        $this->navigate($this->around->choosingLeadsTo($this->around->stackNamed($id)));
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
     *
     * @return list<AStackToChooseAsShown>
     */
    public function stacksToChooseFrom(): array
    {
        return $this->choosingAStack ? $this->around->stacksToChooseFrom($this->stack()) : [];
    }
}
