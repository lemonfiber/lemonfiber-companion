<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * One of the outside watcher's monitors, as the stack reports it.
 */
final readonly class AMonitor
{
    private function __construct(
        private string $name,
        private KindOfMonitor $kind,
        private HowAMonitorStands $stands,
        private Instant $since,
        private bool $active,
        private WhenItSays $lastReading,
        private ?WhatAMonitorCovers $covers,
        private ?WhichWayItLooks $path,
        private ?AHeartbeat $heartbeat,
        private ?AMonitorNotSetUp $notSetUp,
    ) {}

    /**
     * Why it could not be set up to watch what it should, or that it was set up as meant.
     *
     * @template TInstead of object
     * @template TAsMeant of object
     *
     * @param  Closure(AMonitorNotSetUp): TInstead $instead
     * @param  Closure(): TAsMeant                 $asMeant
     * @return TInstead|TAsMeant
     */
    public function setUp(Closure $instead, Closure $asMeant): object
    {
        return $this->notSetUp instanceof AMonitorNotSetUp ? $instead($this->notSetUp) : $asMeant();
    }

    /** A monitor switched on, by its name, its kind, what it last said and since when, and when it last took a reading. */
    public static function named(string $name, KindOfMonitor $kind, HowAMonitorStands $stands, Instant $since, WhenItSays $lastReading): self
    {
        return new self($name, $kind, $stands, $since, active: true, lastReading: $lastReading, covers: null, path: null, heartbeat: null, notSetUp: null);
    }

    /** The same monitor, switched off. */
    public function switchedOff(): self
    {
        return new self($this->name, $this->kind, $this->stands, $this->since, active: false, lastReading: $this->lastReading, covers: $this->covers, path: $this->path, heartbeat: $this->heartbeat, notSetUp: $this->notSetUp);
    }

    /** The same monitor, covering this thing. */
    public function covering(WhatAMonitorCovers $covers): self
    {
        return new self($this->name, $this->kind, $this->stands, $this->since, $this->active, $this->lastReading, $covers, $this->path, $this->heartbeat, $this->notSetUp);
    }

    /** The same monitor, reaching what it covers this way. */
    public function reachedBy(WhichWayItLooks $path): self
    {
        return new self($this->name, $this->kind, $this->stands, $this->since, $this->active, $this->lastReading, $this->covers, $path, $this->heartbeat, $this->notSetUp);
    }

    /** The same monitor, waiting for this heartbeat. */
    public function waitingFor(AHeartbeat $heartbeat): self
    {
        return new self($this->name, $this->kind, $this->stands, $this->since, $this->active, $this->lastReading, $this->covers, $this->path, $heartbeat, $this->notSetUp);
    }

    /** The same monitor, which could not be set up to watch what it should. */
    public function notSetUp(AMonitorNotSetUp $why): self
    {
        return new self($this->name, $this->kind, $this->stands, $this->since, $this->active, $this->lastReading, $this->covers, $this->path, $this->heartbeat, $why);
    }

    /** Its name. */
    public function name(): string
    {
        return $this->name;
    }

    /** How it looks. */
    public function kind(): KindOfMonitor
    {
        return $this->kind;
    }

    /** What it last said. */
    public function stands(): HowAMonitorStands
    {
        return $this->stands;
    }

    /** Since when it has said so. */
    public function since(): Instant
    {
        return $this->since;
    }

    /** Whether it is switched on. */
    public function isActive(): bool
    {
        return $this->active;
    }

    /** When it last took a reading, or that it will not say. */
    public function lastReading(): WhenItSays
    {
        return $this->lastReading;
    }

    /** Whether it covers this service. */
    public function coversTheService(ServiceId $service): bool
    {
        return $this->covers instanceof WhatAMonitorCovers && $this->covers->isTheService($service);
    }

    /**
     * What it covers where lemonfiber made it, or that it covers nothing it names.
     *
     * @template TThing of object
     * @template TNothing of object
     *
     * @param  Closure(WhatAMonitorCovers): TThing $thing
     * @param  Closure(): TNothing                 $nothing
     * @return TThing|TNothing
     */
    public function covers(Closure $thing, Closure $nothing): object
    {
        return $this->covers instanceof WhatAMonitorCovers ? $thing($this->covers) : $nothing();
    }

    /**
     * Which way it reaches what it covers, or that it does not say.
     *
     * @template TBy of object
     * @template TUnsaid of object
     *
     * @param  Closure(WhichWayItLooks): TBy $by
     * @param  Closure(): TUnsaid            $unsaid
     * @return TBy|TUnsaid
     */
    public function reached(Closure $by, Closure $unsaid): object
    {
        return $this->path instanceof WhichWayItLooks ? $by($this->path) : $unsaid();
    }

    /**
     * The heartbeat it waits for, or that it waits for none.
     *
     * @template TWaits of object
     * @template TNone of object
     *
     * @param  Closure(AHeartbeat): TWaits $waits
     * @param  Closure(): TNone            $none
     * @return TWaits|TNone
     */
    public function heartbeat(Closure $waits, Closure $none): object
    {
        return $this->heartbeat instanceof AHeartbeat ? $waits($this->heartbeat) : $none();
    }
}
