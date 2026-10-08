<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

/**
 * How each installed plugin's source stands, as the stack asked them now.
 *
 * Only the listing of what is installed asks; an answer about an install asks
 * none, and a plugin it did not ask about is one it said nothing of.
 */
final readonly class ThePluginSources
{
    /** @param list<ASourceAsked> $sources */
    private function __construct(private array $sources) {}

    /** These, in the stack's order. */
    public static function these(ASourceAsked ...$sources): self
    {
        return new self(array_values($sources));
    }

    /** How this plugin's source stands, or that the stack said nothing of it. */
    public function of(APlugin $plugin): HowItsSourceStands
    {
        foreach ($this->sources as $source) {
            if ($source->isOf($plugin)) {
                return $source->standing();
            }
        }

        return HowItsSourceStands::notSaid();
    }
}
