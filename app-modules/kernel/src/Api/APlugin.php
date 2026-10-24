<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;
use function trim;

/**
 * One plugin, installed or as an install would settle it: what it is, what vouches for it, its recipes and the services it placed.
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
        private Services $services,
        private TheCredentialGrants $grants,
    ) {}

    /** The plugin, with each service it placed; a blank id or version is refused. */
    public static function named(string $id, string $version, string $name, WhatVouchesForAPlugin $vouched, TheRecipes $recipes, ServiceId ...$services): self
    {
        foreach (['plugin' => $id, 'version' => $version] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($id, $version, trim($name), $vouched, $recipes, Services::these(...$services), TheCredentialGrants::of());
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

    /** Whether it can be updated: its record names the source it came from, which an update fetches again. */
    public function canBeUpdated(): bool
    {
        return $this->vouched->source() !== '';
    }

    /** Each service it placed, in the record's order. */
    public function services(): Services
    {
        return $this->services;
    }

    /** The same plugin, holding these grants of credentials of other services. */
    public function holding(TheCredentialGrants $grants): self
    {
        return new self($this->id, $this->version, $this->name, $this->vouched, $this->recipes, $this->services, $grants);
    }

    /** Every credential of another service the operator granted it, as a third-party plugin. */
    public function grants(): TheCredentialGrants
    {
        return $this->grants;
    }

    /** Every recipe, in the manifest's order. */
    public function recipes(): TheRecipes
    {
        return $this->recipes;
    }

    /** Every approval the recipes an install or an update runs ask for, as the stack spells each, once; a demand recipe asks for its own each time it runs. */
    public function approvals(): PluginLines
    {
        $approvals = [];

        foreach ($this->recipes as $recipe) {
            if ($recipe->runs() !== WhenARecipeRuns::Install) {
                continue;
            }

            foreach ($recipe->approvals() as $approval) {
                $approvals[$approval] = $approval;
            }
        }

        return PluginLines::under('approval', ...array_values($approvals));
    }
}
