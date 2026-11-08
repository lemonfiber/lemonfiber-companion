<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What sort of alert one is: the kind of notification the core filed it as, and how much it matters.
 *
 * What the operator's choices on the notification page are weighed against.
 * A resolution carries the severity of what resolved, so it reaches the phone
 * exactly when its onset did.
 */
final readonly class AnAlertsSort
{
    private function __construct(private KindOfNotification $kind, private Severity $severity) {}

    /** An alert of this kind, at this severity. */
    public static function of(KindOfNotification $kind, Severity $severity): self
    {
        return new self($kind, $severity);
    }

    /** The kind of notification it is. */
    public function kind(): KindOfNotification
    {
        return $this->kind;
    }

    /** How much it matters. */
    public function severity(): Severity
    {
        return $this->severity;
    }
}
