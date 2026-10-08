<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Taking a plugin off the machine, agreed to against the reading the operator was shown.
 *
 * The yes quotes the reading's name, so what goes is what was listed, or the
 * stack refuses a reading that has moved on.
 */
final readonly class APluginRemovalAgreed
{
    private function __construct(private string $plugin, private string $agreement) {}

    /** Agreed against that reading of removing this plugin; anything else is refused. */
    public static function after(ThePlugins $reading, APlugin $plugin): self
    {
        if (! $reading->readsARemovalOf($plugin) || $reading->agreement() === '') {
            throw APluginActWasNotRehearsed::before(ExtendingIt::Remove);
        }

        return new self($plugin->id(), $reading->agreement());
    }

    /** The plugin, by its id. */
    public function plugin(): string
    {
        return $this->plugin;
    }

    /** The name of the reading agreed to. */
    public function agreement(): string
    {
        return $this->agreement;
    }
}
