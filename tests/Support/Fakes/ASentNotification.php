<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use NativePHP\LocalNotifications\ScheduledNotification;

/**
 * One notification as the plugin's builder received it.
 *
 * Dispatching is turned off in the constructor, which is the only reason this
 * is safe to keep a reference to: the plugin sends in `__destruct()` and
 * `execute()` is protected, so a builder that is held alive is a notification
 * that is never shown. Off a handset it would reach `nativephp_call()` and
 * there is none.
 *
 * `title()` and `body()` are overridden to record rather than to store, because
 * what the contract asserts is which wording the adapter chose — and on the
 * guarded arm, that no stack name appears in it at all.
 */
final class ASentNotification extends ScheduledNotification
{
    private string $titleSaid = '';

    private string $bodySaid = '';

    public function __construct(string $id)
    {
        parent::__construct($id);

        $this->withoutDispatching();
    }

    public function title(string $title): static
    {
        $this->titleSaid = $title;

        return $this;
    }

    public function body(string $body): static
    {
        $this->bodySaid = $body;

        return $this;
    }

    /** The title the adapter composed. */
    public function titleSaid(): string
    {
        return $this->titleSaid;
    }

    /** The body the adapter composed. */
    public function bodySaid(): string
    {
        return $this->bodySaid;
    }
}
