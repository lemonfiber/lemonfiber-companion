<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which of the services a start would bring up the stack says are already running.
 *
 * Either the stack read what is running, and names the services that are, or
 * it could not read it and says so. A service it names is not one a start
 * would bring up, and a rehearsal whose running could not be read says that
 * rather than letting every service read as one that would start.
 */
final readonly class WhatIsAlreadyRunning
{
    private function __construct(private ?Services $running) {}

    /** The stack read what is running, and these are. */
    public static function these(Services $running): self
    {
        return new self($running);
    }

    /** The stack could not read what is running. */
    public static function couldNotBeRead(): self
    {
        return new self(null);
    }

    /** Whether the stack read what is running. */
    public function wasRead(): bool
    {
        return $this->running instanceof Services;
    }

    /** Whether the stack says this service is already running; never where nothing was read. */
    public function holds(ServiceId $service): bool
    {
        foreach ($this->running ?? Services::none() as $running) {
            if ($running->isTheSameAs($service)) {
                return true;
            }
        }

        return false;
    }
}
