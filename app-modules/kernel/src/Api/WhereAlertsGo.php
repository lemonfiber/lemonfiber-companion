<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Where one machine's alerts go: how delivery stands, every back-end in the core's order, and the ones it could not read. */
final readonly class WhereAlertsGo
{
    private function __construct(
        public HowDeliveryStands $stands,
        public AlertBackEnds $backEnds,
        public UnreadBackEnds $unread,
    ) {}

    public static function read(HowDeliveryStands $stands, AlertBackEnds $backEnds, UnreadBackEnds $unread): self
    {
        return new self($stands, $backEnds, $unread);
    }

    /** Whether nothing is set up and nothing went unread, which is the one *Not set up* frame. */
    public function isNotSetUp(): bool
    {
        return $this->stands === HowDeliveryStands::Unconfigured && $this->backEnds->isEmpty() && $this->unread->isEmpty();
    }
}
