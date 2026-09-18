<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Asked;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatTheCoreDecided;
use Modules\Kernel\Api\WhyNothingIsShown;

use function sprintf;

/**
 * A notification centre that remembers instead of showing.
 *
 * What every test that needs "the operator was told" hands its subject. The
 * contract test is what keeps it honest: it is run against the same assertions
 * as {@see \Modules\Device\Api\PlatformNotifier}, so a fake that is easier to
 * satisfy than the platform fails there rather than quietly making the suite
 * green (`G2`).
 *
 * It records which arm a notification took, not the words, and that is
 * deliberate. The words are the translator's and belong to `L1`'s tests; what
 * matters here is whether a locked device was handed the guarded form — which
 * is a fact about the type and can be asserted without a catalogue.
 */
final class ANotifierInMemory implements Notifier
{
    /** @var list<string> */
    private array $told = [];

    private int $asked = 0;

    private function __construct(private Asked $standing) {}

    /** A device whose operator has allowed notifications. */
    public static function allowed(): self
    {
        return new self(Asked::Granted);
    }

    /** A device whose operator has declined, and must not be asked again. */
    public static function refused(): self
    {
        return new self(Asked::Declined);
    }

    /** A device whose operator has not been asked yet. */
    public static function unasked(): self
    {
        return new self(Asked::NotYet);
    }

    public function standing(): Asked
    {
        return $this->standing;
    }

    public function ask(): Asked
    {
        // Records that it was asked, and refuses to ask twice. The fake has to
        // keep the never-ask-again rule too — a fake that re-asks happily is a
        // fake that lets a caller violating it pass every test it is used in.
        if ($this->standing->mayAsk()) {
            $this->asked++;
            $this->standing = Asked::Granted;
        }

        return $this->standing;
    }

    /** How many times the operator was actually prompted. */
    public function timesAsked(): int
    {
        return $this->asked;
    }

    public function show(Notification $notification): Shown
    {
        if (! $this->standing->mayProceed()) {
            return Shown::withheld(WhyNothingIsShown::NotificationsAreNotPermitted);
        }

        $this->told[] = $notification->either(
            plain: fn(WhatTheCoreDecided $says, StackId $about): Code
                => Code::of(sprintf('plain %s %s', $says->shown(), $about->stored())),
            guarded: fn(WhatTheCoreDecided $says): Code => Code::of(sprintf('guarded %s', $says->shown())),
        )->shown();

        return Shown::delivered();
    }

    /**
     * What this device was asked to show, in order.
     *
     * @return list<string>
     */
    public function told(): array
    {
        return $this->told;
    }
}
