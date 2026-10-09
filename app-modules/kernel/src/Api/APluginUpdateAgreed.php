<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Updating a plugin, agreed to against the reading the operator was shown.
 *
 * Two agreements, as for an install ({@see APluginInstallAgreed}): the yes
 * quotes the reading's name, and each value the new version's recipes would
 * carry elsewhere is approved as itself. The source is the one the plugin was
 * installed from, as its record names it.
 */
final readonly class APluginUpdateAgreed
{
    private function __construct(private string $plugin, private APluginSource $source, private string $agreement, private PluginLines $approved) {}

    /** Agreed against that reading of updating this plugin; anything else is refused. */
    public static function after(ThePlugins $reading, APlugin $plugin, PluginLines $approved): self
    {
        if (! $reading->readsAnUpdateOf($plugin) || $reading->agreement() === '') {
            throw APluginActWasNotRehearsed::before(ExtendingIt::Update);
        }

        return new self($plugin->id(), APluginSource::typed($plugin->vouched()->source()), $reading->agreement(), $approved->alsoIn($reading->approvals()));
    }

    /** The plugin, by its id. */
    public function plugin(): string
    {
        return $this->plugin;
    }

    /** Where the new version comes from: the source the plugin was installed from. */
    public function source(): APluginSource
    {
        return $this->source;
    }

    /** The name of the reading agreed to. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** Every value approved, as the reading spells each. */
    public function approved(): PluginLines
    {
        return $this->approved;
    }
}
