<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A value a recipe asks the operator for when it runs, as the manifest asks for it.
 */
final readonly class AnInputARecipeAsks
{
    private function __construct(private string $name, private string $ask) {}

    /** The input by its name, and the question the manifest puts for it; either blank is refused. */
    public static function named(string $name, string $ask): self
    {
        foreach (['name' => $name, 'ask' => $ask] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self(trim($name), trim($ask));
    }

    /** The input's name. */
    public function name(): string
    {
        return $this->name;
    }

    /** What the manifest asks the operator for, in its words. */
    public function ask(): string
    {
        return $this->ask;
    }
}
