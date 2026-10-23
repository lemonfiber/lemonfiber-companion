<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A request lemonfiber makes on an installed plugin's behalf, attributed to the plugin that declared it.
 *
 * Neither lemonfiber's own, which it has a reason of its own to make, nor a
 * service's, which no service made: the third kind, kept apart from both.
 */
final readonly class ARequestOnAPluginsBehalf
{
    private function __construct(
        private APluginId $plugin,
        private string $destination,
        private string $purpose,
    ) {}

    /** What a plugin declared: where the request goes and why it may be made; a blank one is refused. */
    public static function declaredBy(APluginId $plugin, string $destination, string $purpose): self
    {
        if (trim($destination) === '') {
            throw RequestSaysNothing::about('destination');
        }

        if (trim($purpose) === '') {
            throw RequestSaysNothing::about('purpose');
        }

        return new self($plugin, $destination, $purpose);
    }

    /** The plugin that declared it. */
    public function plugin(): APluginId
    {
        return $this->plugin;
    }

    /** Where it goes, as the plugin's manifest named it. */
    public function destination(): string
    {
        return $this->destination;
    }

    /** Why it may be made. */
    public function purpose(): string
    {
        return $this->purpose;
    }
}
