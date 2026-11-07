<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** A part this build carries that a start offers to install, by its plugin, with why its install could not be read where it could not. */
final readonly class AnInstallAStartOffers
{
    private function __construct(private string $plugin, private string $why) {}

    /** The install; a blank plugin is refused, and `why` is empty where its install could be read. */
    public static function of(string $plugin, string $why): self
    {
        if (trim($plugin) === '') {
            throw PluginSaysNothing::about('plugin');
        }

        return new self($plugin, trim($why));
    }

    /** The plugin it would install. */
    public function plugin(): string
    {
        return $this->plugin;
    }

    /** Why its install could not be read, in the stack's words, or empty where it could. */
    public function why(): string
    {
        return $this->why;
    }
}
