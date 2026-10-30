<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One setting in the stack's settings store, flattened for a template. */
final readonly class AStackSettingAsShown
{
    /**
     * @param string                         $name      its name, which changing it names
     * @param ALineToSay|null $whose whose it is, or null where the screen says that already
     * @param bool                           $isSet     whether the operator recorded a value
     * @param string                         $value     that value, blank where none is recorded
     * @param list<AServiceReadingItAsShown> $readers   each service that reads it
     * @param list<AChoiceAsShown>           $choices   every value it accepts, or none where it is typed
     */
    public function __construct(
        public string $name,
        public ?ALineToSay $whose,
        public bool $isSet,
        public string $value,
        public array $readers,
        public array $choices,
    ) {}
}
