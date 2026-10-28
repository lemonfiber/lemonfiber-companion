<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One credential of another service an install would hand a third-party plugin, with its own switch apart from the offer.
 *
 * Named by the setting it is held under and never by its value. The switch is
 * named by its place among the install's approvals, as a value's is.
 */
final readonly class AGrantAskedAsShown
{
    /**
     * @param string   $credential the setting the credential is held under
     * @param string   $whose      the service whose credential it is
     * @param string   $fills      the capability of the plugin's own it would fill
     * @param ASwitchAsShown|null $switch its switch, or null where the screen offers none for it
     */
    public function __construct(
        public string $credential,
        public string $whose,
        public string $fills,
        public ?ASwitchAsShown $switch,
    ) {}
}
