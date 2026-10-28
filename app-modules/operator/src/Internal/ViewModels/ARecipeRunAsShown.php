<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Running one of a plugin's demand recipes: the rehearsal a yes answers, or what the run came to, flattened for a template. */
final readonly class ARecipeRunAsShown
{
    /**
     * @param string                    $plugin     the plugin, by its id
     * @param bool                      $isAReading whether this is the rehearsal, nothing run
     * @param bool                      $agreeable  whether a yes may be given against it
     * @param string                    $headline   the catalogue key for what it is or came to
     * @param ARecipeAsShown            $recipe     the recipe, its steps in order and every value it would carry
     * @param ?string                    $why        why the run did not hold, in the stack's words, or empty
     * @param list<AStepThatRanAsShown> $ran        every step and what it came to; empty for a rehearsal
     */
    public function __construct(
        public string $plugin,
        public bool $isAReading,
        public bool $agreeable,
        public string $headline,
        public ARecipeAsShown $recipe,
        public ?string $why,
        public array $ran,
    ) {}
}
