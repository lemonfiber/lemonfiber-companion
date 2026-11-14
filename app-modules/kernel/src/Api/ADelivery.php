<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One alert a back-end was handed: when, what kind, and how it ended. */
final readonly class ADelivery
{
    public function __construct(
        public Instant $at,
        public KindOfNotification $kind,
        public HowADeliveryEnded $ended,
    ) {}
}
