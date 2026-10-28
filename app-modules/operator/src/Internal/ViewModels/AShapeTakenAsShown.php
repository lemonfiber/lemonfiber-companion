<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One service taking a privileged shape, with its own switch apart from the offer.
 *
 * The switch is named by its place among the install's approvals, as a value's is.
 */
final readonly class AShapeTakenAsShown
{
    /**
     * @param string       $service   the service taking it
     * @param string       $neededFor the catalogue key for why it takes the shape
     * @param list<string> $grants    the kernel capabilities it is granted
     * @param list<string> $devices   the devices it is given
     * @param ASwitchAsShown|null     $switch its switch, or null where the screen offers none for it
     */
    public function __construct(
        public string $service,
        public string $neededFor,
        public array $grants,
        public array $devices,
        public ?ASwitchAsShown $switch,
    ) {}
}
