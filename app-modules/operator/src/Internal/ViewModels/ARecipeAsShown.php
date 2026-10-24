<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One of a plugin's recipes, as its steps in order and every value it could carry where, and whether it runs only when asked for. */
final readonly class ARecipeAsShown
{
    /**
     * @param string                     $title what it accomplishes
     * @param string                     $why   why it is worth running, or empty
     * @param list<ARecipeStepAsShown>   $steps every call, in the recipe's order
     * @param list<AValueCarriedAsShown> $pairs every value it could carry, and where
     * @param bool                       $onDemand whether it runs only when asked for by name
     * @param list<string>               $asks  what it asks the operator for when it runs, in the manifest's words
     */
    public function __construct(
        public string $title,
        public string $why,
        public array $steps,
        public array $pairs,
        public bool $onDemand,
        public array $asks,
    ) {}
}
