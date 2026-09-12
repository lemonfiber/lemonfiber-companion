<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Notification;
use Modules\Kernel\Api\Notifier;
use Modules\Kernel\Api\Shown;
use Modules\Kernel\Api\StackId;
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

    private function __construct(private readonly bool $permitted) {}

    /** A device whose operator has allowed notifications. */
    public static function allowed(): self
    {
        return new self(permitted: true);
    }

    /** A device whose operator has not, so nothing may be shown. */
    public static function refused(): self
    {
        return new self(permitted: false);
    }

    public function isPermitted(): bool
    {
        return $this->permitted;
    }

    public function show(Notification $notification): Shown
    {
        if (! $this->permitted) {
            return Shown::withheld(WhyNothingIsShown::NotificationsAreNotPermitted);
        }

        $this->told[] = $notification->either(
            plain: fn(Code $says, StackId $about): Code => Code::of(sprintf('plain %s %s', $says->shown(), $about->stored())),
            guarded: fn(Code $says): Code => Code::of(sprintf('guarded %s', $says->shown())),
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
