<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One of a plugin's recipes: every call it makes, in its order, and every value it could carry where.
 *
 * Never summarised: the steps are the recipe, and the pairs are what the
 * operator approves one by one.
 */
final readonly class ARecipe
{
    private function __construct(
        private string $id,
        private string $title,
        private string $why,
        private TheRecipeSteps $steps,
        private TheValuesItCarries $pairs,
    ) {}

    /** The recipe, as the reading lists it; a blank id or title is refused. */
    public static function of(string $id, string $title, string $why, TheRecipeSteps $steps, TheValuesItCarries $pairs): self
    {
        foreach (['id' => $id, 'title' => $title] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($id, $title, trim($why), $steps, $pairs);
    }

    /** The recipe's id within the plugin. */
    public function id(): string
    {
        return $this->id;
    }

    /** What it accomplishes, in one line. */
    public function title(): string
    {
        return $this->title;
    }

    /** Why it is worth running, or empty where the manifest does not say. */
    public function why(): string
    {
        return $this->why;
    }

    /** Every call it makes, in its order. */
    public function steps(): TheRecipeSteps
    {
        return $this->steps;
    }

    /** Every value it could carry, and where to. */
    public function pairs(): TheValuesItCarries
    {
        return $this->pairs;
    }
}
