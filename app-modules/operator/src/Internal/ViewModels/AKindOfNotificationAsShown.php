<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One kind of notification a stack raises, as the notification page draws the switch for it. */
final readonly class AKindOfNotificationAsShown
{
    /**
     * @param string $kind the kind, as the switch hands it back
     * @param string $said the key for what it is called
     * @param bool   $isOn whether it may reach the phone
     */
    public function __construct(
        public string $kind,
        public string $said,
        public bool $isOn,
    ) {}
}
