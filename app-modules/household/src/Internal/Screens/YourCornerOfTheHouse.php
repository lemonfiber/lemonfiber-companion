<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Connection\Api\RemovingAStack;
use Modules\Connection\Api\WhatBecameOfRemoving;
use Modules\Household\Internal\ViewModels\AHouseToChooseAsShown;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Wayfinding\Api\AScreenWithoutAStack;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhoTheSettingsSpeakTo;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * The member's Profile tab: changing house, App settings, and taking this
 * house off the phone.
 *
 * Changing house opens the houses this phone holds, by name and nothing else,
 * as a sheet over the tab. App settings is the same screen everybody reaches,
 * spoken in household words. Taking the house off the phone is asked here,
 * where the question can say that nothing changes at the house, and then
 * lands where the app goes once a stack is gone.
 */
#[Lazy]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class YourCornerOfTheHouse extends NativeComponent
{
    use FindsItsWayAroundTheHouse;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::your-corner-of-the-house';

    /**
     * The house, once read, so the frame drawn after it is taken off the phone
     * still has its name: the route then names a house the phone no longer holds.
     */
    public ?Stack $held = null;

    /** Whether the houses this phone holds are open over the tab. */
    public bool $choosingAHouse = false;

    /** Whether the member is being asked whether to take this house off the phone. */
    public bool $confirmingTheRemoval = false;

    /** Whether the phone would not take the house off, the last time it was asked. */
    public bool $removalRefused = false;

    public function __construct(
        private readonly TheWayAround $around,
        private readonly Stacks $stacks,
        private readonly RemovingAStack $removing,
    ) {}

    /** The house this screen is about, read once from the route and held. */
    public function stack(): Stack
    {
        return $this->held ??= $this->around->stackOn($this);
    }

    /** Open the houses this phone holds over the tab. */
    public function switchHouse(): void
    {
        $this->choosingAHouse = true;
    }

    /** Close the houses without changing house. */
    public function stayInThisHouse(): void
    {
        $this->choosingAHouse = false;
    }

    /**
     * Every house this phone holds, in its order, each by name and whether it is the one the member is in.
     *
     * Read only while the list is open, so a frame with it closed asks the
     * phone's store nothing.
     *
     * @return list<AHouseToChooseAsShown>
     */
    public function housesToChooseFrom(): array
    {
        if (! $this->choosingAHouse) {
            return [];
        }

        $here = $this->stack();
        $houses = [];

        foreach ($this->stacks->configured() as $stack) {
            $houses[] = new AHouseToChooseAsShown($stack->id()->stored(), $stack->name()->shown(), $stack->is($here));
        }

        return $houses;
    }

    /** Open the house this phone calls `$id`, where choosing it leads for whoever is signed into it. */
    public function openTheHouse(string $id): void
    {
        $this->choosingAHouse = false;
        $this->navigate($this->around->choosingLeadsTo($this->around->stack(StackId::rememberedAs($id))));
    }

    /** Where adding a house begins: pairing, which goes on to signing in. */
    public function addAHouse(): string
    {
        return AScreenWithoutAStack::PairByScanning->value;
    }

    /** App settings, which this screen opens in household words. */
    public function appSettings(): string
    {
        return AScreenWithoutAStack::Settings->value;
    }

    /**
     * What App settings is handed when this screen opens it.
     *
     * @return array<string, string>
     */
    public function appSettingsSpeakTo(): array
    {
        return [AScreenWithoutAStack::SETTINGS_SPEAK_TO => WhoTheSettingsSpeakTo::AMember->value];
    }

    /** The member asked to take this house off the phone; they are asked whether they mean it, on this screen. */
    public function askToRemove(): void
    {
        $this->confirmingTheRemoval = true;
        $this->removalRefused = false;
    }

    /** The member kept the house. */
    public function keepTheHouse(): void
    {
        $this->confirmingTheRemoval = false;
    }

    /**
     * Take this house off the phone, and land where the app goes once it is gone.
     *
     * Where to land is asked before the removal, while the house is still in
     * the phone's order.
     */
    public function removeTheHouse(): void
    {
        $landing = $this->around->afterRemoving($this->stack());

        if ($this->removing->remove($this->stack()->id()) === WhatBecameOfRemoving::Refused) {
            $this->confirmingTheRemoval = false;
            $this->removalRefused = true;

            return;
        }

        $this->replaceTheWholeStack($landing);
    }
}
