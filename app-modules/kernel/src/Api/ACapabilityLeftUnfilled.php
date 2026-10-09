<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A capability that would have nothing filling it once a plugin goes, and the plugin that fills it now.
 *
 * Named with what fills it, because the sentence an operator acts on is
 * *this is the only thing filling it*, and a capability alone does not say so.
 */
final readonly class ACapabilityLeftUnfilled
{
    private function __construct(private string $capability, private string $filledBy) {}

    /** The capability and what fills it now; either blank is refused. */
    public static function of(string $capability, string $filledBy): self
    {
        foreach (['capability' => $capability, 'filled_by' => $filledBy] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($capability, $filledBy);
    }

    /** What would be left unfilled. */
    public function capability(): string
    {
        return $this->capability;
    }

    /** The plugin filling it now, which is the one going. */
    public function filledBy(): string
    {
        return $this->filledBy;
    }
}
