<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One secret a plugin's services read, never with its value, flattened for a template. */
final readonly class ASecretAsShown
{
    /**
     * @param string                         $name      its name
     * @param ALineToSay|null $whose whose it is, or null where it is the plugin's own
     * @param string                         $heldSaid  the catalogue key saying whether a value is held
     * @param string                         $fromSaid  the catalogue key saying where its value came from
     * @param list<AServiceReadingItAsShown> $readers   each service that reads it
     */
    public function __construct(
        public string $name,
        public ?ALineToSay $whose,
        public string $heldSaid,
        public string $fromSaid,
        public array $readers,
    ) {}
}
