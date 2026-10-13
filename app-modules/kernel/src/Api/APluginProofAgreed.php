<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The operator's yes to proving a plugin again, quoting the reading that said what the proof would ask.
 */
final readonly class APluginProofAgreed
{
    private function __construct(private string $plugin, private string $agreement) {}

    /** Agreed against that reading of proving this plugin again; anything else is refused. */
    public static function after(ThePlugins $reading, APlugin $plugin): self
    {
        if (! $reading->readsAProofOf($plugin) || $reading->agreement() === '') {
            throw APluginActWasNotRehearsed::before(ExtendingIt::Prove);
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
