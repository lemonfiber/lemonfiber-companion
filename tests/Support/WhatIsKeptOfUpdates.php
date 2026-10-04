<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatAReleaseDelivers;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\ReadingsInMemory;

/** A seal, a store, and what decides between them, over the two stand-ins, and the readings a test keeps. */
final readonly class WhatIsKeptOfUpdates
{
    public KeepingTheLastUpkeep $keeping;

    public function __construct(public ASealInMemory $seal, public ReadingsInMemory $store)
    {
        $this->keeping = new KeepingTheLastUpkeep($seal, $store);
    }

    public static function onAPhoneThatSeals(): self
    {
        return new self(ASealInMemory::working(), ReadingsInMemory::empty());
    }

    /**
     * An update waiting, with every part a reading can have: two releases, one
     * withdrawn and one with nothing said of it, two services that would move
     * and one of them for good, notes out of step, a file the operator edited,
     * and what became of the last update.
     */
    public static function aReadingWithEveryPart(): Upkeep
    {
        return Upkeep::reported(
            AgainstThePins::UpdatesAvailable,
            Releases::these(
                Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')),
                Release::called('4.0.16', noticeable: false, withdrawn: true, delivers: WhatAReleaseDelivers::saidNothing()),
            ),
            Services::these(ServiceId::called('jellyfin'), ServiceId::called('sonarr')),
            Services::these(ServiceId::called('sonarr')),
            HowServicesTookIt::these(HowAServiceTookIt::of(ServiceId::called('jellyfin'), HowItEnded::Updated, HowToUndoIt::Rollback)),
            HowTheNotesStand::Stale,
            TheStackEdits::these(AStackEdit::at('compose.yaml', "- PUID=1001\n+ PUID=1000")),
        )->runningOn(Release::called('4.1.0', noticeable: true, withdrawn: false, delivers: WhatAReleaseDelivers::said('Adds series search.')));
    }

    /** A stack on every pin, naming no release, with nothing in its record. */
    public static function aReadingOfAStackThatIsCurrent(): Upkeep
    {
        return Upkeep::reported(
            AgainstThePins::Current,
            Releases::none(),
            Services::none(),
            Services::none(),
            HowServicesTookIt::none(),
            HowTheNotesStand::Current,
            TheStackEdits::none(),
        );
    }

    /** A value kept for a stack as though a reading had been, sealed by this phone. */
    public function holdsSealed(string $written, StackId $for, Instant $readAt): self
    {
        $this->seal->seal(Unsealed::of($written))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->store->keep($this->seal->stack($for), $payload, Shape::One, $readAt),
            refused: static fn(): Noted => Noted::notKept(),
        );

        return $this;
    }
}
