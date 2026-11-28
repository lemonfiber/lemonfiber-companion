<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;

use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AWakeToRegister;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RegisteringAWake;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatRegisteringAWakeCameTo;
use Modules\Kernel\Api\WhatRemovingAWakeCameTo;
use Modules\Kernel\Api\WhatTheRefusalNamed;

use function sprintf;

/**
 * Stacks that keep the endpoints phones register with them, in memory, each stack its own, for a test to arrange and read.
 *
 * Held to `RegisteringAWakeContractTest` beside the adapter: a name another
 * endpoint holds is refused in the stack's words, a name it holds none under
 * is removed all the same, and a stack that cannot be reached keeps what it had.
 */
final class AStackThatKeepsWakes implements RegisteringAWake
{
    /** What the stack says of a name another endpoint holds. */
    public const string NAME_TAKEN = 'Another push endpoint is registered under that name.';

    /** What the stack says of an address that is not `https`. */
    public const string NOT_HTTPS = 'A push endpoint is reached over https, and that address is not.';

    /** @var array<string, string> each endpoint a stack keeps, by stack and name */
    private array $endpoints = [];

    /** How many times the stack was asked anything. */
    private int $asked = 0;

    private function __construct(private bool $reachable, private readonly bool $refusingEveryAddress) {}

    public static function reachable(): self
    {
        return new self(reachable: true, refusingEveryAddress: false);
    }

    public static function unreachable(): self
    {
        return new self(reachable: false, refusingEveryAddress: false);
    }

    /** A stack that refuses every address as not `https`. */
    public static function refusingTheAddress(): self
    {
        return new self(reachable: true, refusingEveryAddress: true);
    }

    public function registeredOn(Stack $stack, Session $session, AWakeToRegister $wake): WhatRegisteringAWakeCameTo
    {
        ++$this->asked;

        if (! $this->reachable) {
            return WhatRegisteringAWakeCameTo::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
        }

        if ($this->refusingEveryAddress) {
            return WhatRegisteringAWakeCameTo::refused(ARefusalInItsWords::said(self::NOT_HTTPS, '', WhatTheRefusalNamed::nothing()));
        }

        $held = $this->onTheStack($stack, $wake->name->shown());

        if (array_key_exists($held, $this->endpoints)) {
            return WhatRegisteringAWakeCameTo::refused(ARefusalInItsWords::said(self::NAME_TAKEN, '', WhatTheRefusalNamed::nothing()));
        }

        $this->endpoints[$held] = $wake->endpoint->address();

        return WhatRegisteringAWakeCameTo::registered($wake->endpoint->fingerprint());
    }

    public function removedFrom(Stack $stack, Session $session, APushName $name): WhatRemovingAWakeCameTo
    {
        ++$this->asked;

        if (! $this->reachable) {
            return WhatRemovingAWakeCameTo::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer));
        }

        unset($this->endpoints[$this->onTheStack($stack, $name->shown())]);

        return WhatRemovingAWakeCameTo::removed();
    }

    /** The endpoint the stack keeps under that name, or empty where it keeps none. */
    public function holds(Stack $stack, string $name): string
    {
        $held = $this->onTheStack($stack, $name);

        return array_key_exists($held, $this->endpoints) ? $this->endpoints[$held] : '';
    }

    /** The stack can be reached again: for a test to arrange. */
    public function comesBack(): self
    {
        $this->reachable = true;

        return $this;
    }

    /** The stack goes out of reach: for a test to arrange. */
    public function goesAway(): self
    {
        $this->reachable = false;

        return $this;
    }

    /** The stack already keeps an endpoint under this name: for a test to arrange. */
    public function alreadyHolds(Stack $stack, string $name, string $endpoint): self
    {
        $this->endpoints[$this->onTheStack($stack, $name)] = $endpoint;

        return $this;
    }

    /** How many times the stack was asked anything. */
    public function timesAsked(): int
    {
        return $this->asked;
    }

    private function onTheStack(Stack $stack, string $name): string
    {
        return sprintf('%s/%s', $stack->id()->stored(), $name);
    }
}
