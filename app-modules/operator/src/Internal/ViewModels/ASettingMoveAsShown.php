<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One setting a change moves, flattened for a template. */
final readonly class ASettingMoveAsShown
{
    /**
     * @param string $setting     the setting, by its name
     * @param bool   $toDefault   whether it goes back to its default
     * @param string $value       what it is to hold, blank where it goes back to its default
     * @param bool   $wasSet      whether it held a value of the operator's before
     * @param string $was         what it held before, blank where it was the default
     * @param string $consequence what moving it costs, in the stack's words, blank where nothing
     */
    public function __construct(
        public string $setting,
        public bool $toDefault,
        public string $value,
        public bool $wasSet,
        public string $was,
        public string $consequence,
    ) {}
}
