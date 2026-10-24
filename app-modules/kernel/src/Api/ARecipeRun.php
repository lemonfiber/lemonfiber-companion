<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Running one of a plugin's demand recipes: what it would do, as a rehearsal reads it, or what it came to.
 *
 * The reading is the account the yes answers; the run is the same recipe with
 * what each of its steps came to.
 */
final readonly class ARecipeRun
{
    private function __construct(private APluginId $plugin, private ARecipe $recipe, private ?WhatARecipeRunDid $did) {}

    /** What running it would do, as the stack read it, nothing run. */
    public static function rehearsed(APluginId $plugin, ARecipe $recipe): self
    {
        return new self($plugin, $recipe, null);
    }

    /** What running it came to. */
    public static function ran(APluginId $plugin, ARecipe $recipe, WhatARecipeRunDid $did): self
    {
        return new self($plugin, $recipe, $did);
    }

    /** The plugin whose recipe it is. */
    public function plugin(): APluginId
    {
        return $this->plugin;
    }

    /** The recipe, with its steps and every value it would carry. */
    public function recipe(): ARecipe
    {
        return $this->recipe;
    }

    /** Whether this is the reading a yes answers, rather than a run. */
    public function isAReading(): bool
    {
        return ! $this->did instanceof WhatARecipeRunDid;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TRehearsed of object
     * @template TRan of object
     *
     * @param Closure(): TRehearsed            $rehearsed
     * @param Closure(WhatARecipeRunDid): TRan $ran
     *
     * @return TRehearsed|TRan
     */
    public function either(Closure $rehearsed, Closure $ran): object
    {
        return $this->did instanceof WhatARecipeRunDid ? $ran($this->did) : $rehearsed();
    }
}
