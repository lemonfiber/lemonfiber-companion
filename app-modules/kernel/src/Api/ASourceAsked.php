<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** How one installed plugin's source stands, named by the plugin it is the source of. */
final readonly class ASourceAsked
{
    private function __construct(private string $plugin, private HowItsSourceStands $standing) {}

    /** The plugin's id and how its source stands; a blank id is refused. */
    public static function of(string $plugin, HowItsSourceStands $standing): self
    {
        if (trim($plugin) === '') {
            throw PluginSaysNothing::about('plugin');
        }

        return new self($plugin, $standing);
    }

    /** Whether this is the source of that plugin. */
    public function isOf(APlugin $plugin): bool
    {
        return $this->plugin === $plugin->id();
    }

    /** How it stands. */
    public function standing(): HowItsSourceStands
    {
        return $this->standing;
    }
}
