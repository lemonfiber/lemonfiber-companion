<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One of a plugin's recipes, as its steps in order and every value it could carry where. */
final readonly class ARecipeAsShown
{
    /**
     * @param string                     $title what it accomplishes
     * @param string                     $why   why it is worth running, or empty
     * @param list<ARecipeStepAsShown>   $steps every call, in the recipe's order
     * @param list<AValueCarriedAsShown> $pairs every value it could carry, and where
     */
    public function __construct(
        public string $title,
        public string $why,
        public array $steps,
        public array $pairs,
    ) {}
}
