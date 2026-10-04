<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Awaiting;
use Modules\Kernel\Api\Daemon;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\HowMuchItMatters;
use Modules\Kernel\Api\HowTheStackIsRunning;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhatItTakesAway;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatLeansOnIt;
use Modules\Services\Api\KeepingWhatItRuns;
use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsInMemory;

/** A seal, a store, and what decides between them, over the two stand-ins, and the listings a test keeps. */
final readonly class WhatIsKeptOfServices
{
    public KeepingWhatItRuns $keeping;

    public function __construct(public ASealInMemory $seal, public ReadingsInMemory $store, public FrozenClock $clock)
    {
        $this->keeping = new KeepingWhatItRuns($seal, $store, $clock);
    }

    /** A phone that seals, its clock reading this. */
    public static function onAPhoneThatSealsAt(Instant $now): self
    {
        return new self(ASealInMemory::working(), ReadingsInMemory::empty(), FrozenClock::at($now));
    }

    /**
     * A stack running part of itself, with every part a listing can have: one
     * service running for a form with another leaning on it, one that exited
     * and stays down, a third the form left out for want of a downloader, what
     * each verb takes away, a stop held until the downloads finish.
     */
    public static function aListingWithEveryPart(): Daemons
    {
        $sonarr = ServiceId::called('sonarr');

        return Daemons::of(
            HowTheStackIsRunning::Degraded,
            Disturbances::of(WhatItTakesAway::atMost(30), WhatItTakesAway::until(Awaiting::Downloads), WhatItTakesAway::atMost(45)),
            Daemon::called('Jellyfin', ServiceId::called('jellyfin'), HowAServiceRuns::Running, HowMuchItMatters::Core, WhatLeansOnIt::these($sonarr))
                ->broughtInBy(Forms::these(Form::called('watching'))),
            Daemon::thatExited('Sonarr', $sonarr, HowAServiceRuns::Failed, HowMuchItMatters::Important, WhatLeansOnIt::nothing(), 137),
            Daemon::called('SABnzbd', ServiceId::called('sabnzbd'), HowAServiceRuns::Absent, HowMuchItMatters::Optional, WhatLeansOnIt::nothing()),
        )->asked(
            Forms::these(Form::called('watching')),
            TheServicesLeftOut::of(AServiceLeftOut::needing(ServiceId::called('sabnzbd'), 'SABnzbd', WhatItWouldNeed::Usenet, Forms::these(Form::called('watching')))),
        );
    }

    /** A stack running nothing, with nothing asked for. */
    public static function aListingOfNothing(): Daemons
    {
        return Daemons::none(Disturbances::of(WhatItTakesAway::atMost(1), WhatItTakesAway::atMost(1), WhatItTakesAway::atMost(1)));
    }

    /** A value kept for a stack as though a listing had been, sealed by this phone. */
    public function holdsSealed(string $written, StackId $for, Instant $readAt): self
    {
        $this->seal->seal(Unsealed::of($written))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->store->keep($this->seal->stack($for), $payload, Shape::One, $readAt),
            refused: static fn(): Noted => Noted::notKept(),
        );

        return $this;
    }
}
