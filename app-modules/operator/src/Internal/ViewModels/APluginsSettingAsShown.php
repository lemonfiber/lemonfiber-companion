<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One setting a plugin's services read, flattened for a template. */
final readonly class APluginsSettingAsShown
{
    /**
     * @param string                         $name      its name, which changing it names
     * @param ALineToSay|null $whose whose it is, or null where it is the plugin's own
     * @param bool                           $isSet     whether the operator recorded a value
     * @param string                         $value     that value, blank where none is recorded
     * @param list<AServiceReadingItAsShown> $readers   each service that reads it
     */
    public function __construct(
        public string $name,
        public ?ALineToSay $whose,
        public bool $isSet,
        public string $value,
        public array $readers,
    ) {}
}
