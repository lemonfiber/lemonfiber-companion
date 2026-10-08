<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;
use function trim;

/**
 * One plugin, installed or as an install would settle it: what it is, what vouches for it, and its recipes.
 *
 * Its id is the name it is installed and recorded under. Its own name is the
 * author's, and empty where the record does not keep one: a name made up here
 * from the id would be this app's, not theirs.
 */
final readonly class APlugin
{
    private function __construct(
        private string $id,
        private string $version,
        private string $name,
        private WhatVouchesForAPlugin $vouched,
        private TheRecipes $recipes,
    ) {}

    /** The plugin; a blank id or version is refused. */
    public static function named(string $id, string $version, string $name, WhatVouchesForAPlugin $vouched, TheRecipes $recipes): self
    {
        foreach (['plugin' => $id, 'version' => $version] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($id, $version, trim($name), $vouched, $recipes);
    }

    /** The name it is installed under. */
    public function id(): string
    {
        return $this->id;
    }

    /** Its own version. */
    public function version(): string
    {
        return $this->version;
    }

    /** What it calls itself, or its id where the record keeps no name. */
    public function shown(): string
    {
        return $this->name === '' ? $this->id : $this->name;
    }

    /** Where it came from and what vouches for it. */
    public function vouched(): WhatVouchesForAPlugin
    {
        return $this->vouched;
    }

    /** Every recipe, in the manifest's order. */
    public function recipes(): TheRecipes
    {
        return $this->recipes;
    }

    /** Every approval its recipes ask for, as the stack spells each, once. */
    public function approvals(): PluginLines
    {
        $approvals = [];

        foreach ($this->recipes as $recipe) {
            foreach ($recipe->pairs() as $pair) {
                if ($pair->asksForApproval()) {
                    $approvals[$pair->approval()] = $pair->approval();
                }
            }
        }

        return PluginLines::under('approval', ...array_values($approvals));
    }
}
