<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One delivery as an operator reads it: how it ended, with the back-end's reason verbatim where it failed, and what kind of alert it carried. */
final readonly class ADeliveryAsShown
{
    public function __construct(
        public AnAlertLineAsShown $ended,
        public string $kindSaid,
    ) {}
}
