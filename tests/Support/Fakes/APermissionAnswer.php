<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Native\Mobile\PushNotifications as Permissions;

/**
 * What the platform has recorded about notification permission.
 *
 * A hand-written subclass rather than a mock, which is what `G1` asks for: a
 * mock asserts on calls and drifts silently when the real class changes, and
 * this fails to compile.
 *
 * It exists because `checkPermission()` reaches `nativephp_call()` and answers
 * `null` wherever that is absent — which is every machine that is not a handset.
 * Without it the adapter's permission path has exactly one reachable answer,
 * and the three that matter go untested.
 *
 * **It also counts.** `N4-R4` is not a question about a return value, it is a
 * question about how many times somebody was interrupted — so the assertion
 * that matters is a count, and something has to be keeping it.
 */
final class APermissionAnswer extends Permissions
{
    private int $prompts = 0;

    private function __construct(private ?string $recorded) {}

    /** A device whose operator has allowed notifications. */
    public static function granted(): self
    {
        return new self('granted');
    }

    /** A device whose operator has declined them. */
    public static function denied(): self
    {
        return new self('denied');
    }

    /** A device whose operator has not been asked. */
    public static function notDetermined(): self
    {
        return new self('not_determined');
    }

    /** iOS's quiet delivery, which is a grant for the purpose of showing something. */
    public static function provisional(): self
    {
        return new self('provisional');
    }

    /** What a bridge with no device answers, and what an unknown word reads as. */
    public static function silent(): self
    {
        return new self(null);
    }

    public function checkPermission(): ?string
    {
        return $this->recorded;
    }

    /**
     * The prompt, recorded rather than raised.
     *
     * Answering `granted` afterwards models a device where the operator said
     * yes — the case in which a second prompt would be least noticeable and
     * therefore most likely to survive review.
     */
    public function prompted(): void
    {
        $this->prompts++;
        $this->recorded = 'granted';
    }

    /** How many times the operator was interrupted. */
    public function prompts(): int
    {
        return $this->prompts;
    }
}
