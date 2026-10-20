<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The name a plugin is installed and recorded under, as a screen about that plugin is reached by.
 */
final readonly class APluginId
{
    private function __construct(private string $named) {}

    /** The plugin's id; a blank one is refused. */
    public static function called(string $plugin): self
    {
        if (trim($plugin) === '') {
            throw PluginSaysNothing::about('plugin');
        }

        return new self(trim($plugin));
    }

    /** The id, as the record spells it. */
    public function named(): string
    {
        return $this->named;
    }
}
