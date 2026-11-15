<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Waking this phone for one stack, flattened for its settings.
 *
 * Not drawn at all where the phone cannot be woken through a distributor or
 * the session is not the operator's.
 */
final readonly class HowWakingIsShown
{
    /**
     * @param bool                    $drawn       whether the switch is drawn
     * @param bool                    $on          whether waking is on for the stack
     * @param bool                    $usable      whether the switch may be turned
     * @param string                  $line        the key for what is said under the switch
     * @param string                  $distributor what the distributor that wakes it is called, or empty
     * @param ASettingAsShown         $choice      the distributors to choose among, none where fewer than two are installed
     * @param ALinkWithItsCode|null   $install     where a distributor is found, where none is installed
     */
    public function __construct(
        public bool $drawn,
        public bool $on,
        public bool $usable,
        public string $line,
        public string $distributor,
        public ASettingAsShown $choice,
        public ?ALinkWithItsCode $install,
    ) {}
}
