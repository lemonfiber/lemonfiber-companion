<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Closure;
use Modules\Kernel\Api\Admitted;
use Modules\Kernel\Api\Admitting;
use Modules\Kernel\Api\Credential;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;

/**
 * A door that answers what a test told it to, and remembers being knocked on.
 *
 * The stand-in every screen test gets, so that no test of a sign-in needs a
 * machine to sign in to. What it remembers is the half a screen cannot assert
 * about itself: *which* stack was knocked on. A screen handed two stacks and
 * offering the password to the wrong one is `N1-R11` broken at the one place it
 * matters most, and a fake that forgot the stack would make that green.
 *
 * **It spends the credential, because the real door does.** `Credential`
 * empties itself when it is offered, and `N1-R7`'s second clause is that
 * nothing keeps one for re-sending. A fake that answered without offering would
 * leave every screen test holding a credential that could be sent again — and
 * the contract both implementations are run against asserts exactly this.
 *
 * Not `readonly`: what was knocked on is written when the knock happens.
 */
final class ADoorThatWasKnockedOn implements Admitting
{
    /** The stack it was last knocked on for, or nothing where it never was. */
    private ?Stack $knockedOn = null;

    /** How many times, which is how a screen that asks twice is caught. */
    private int $knocks = 0;

    /** @param Closure(): Admitted $answer */
    private function __construct(private readonly Closure $answer) {}

    /** A door that opens, with a session lasting until the moment given. */
    public static function opening(Session $session, Instant $until): self
    {
        return new self(static fn(): Admitted => Admitted::opening($session, $until));
    }

    /** A door that does not open, for the reason given. */
    public static function refusing(Obstacle $why): self
    {
        return new self(static fn(): Admitted => Admitted::refused($why));
    }

    /** The stack it was last knocked on for, or nothing where it never was. */
    public function knockedOn(): ?Stack
    {
        return $this->knockedOn;
    }

    /** How many times it was knocked on, which catches a screen that asks twice. */
    public function knocks(): int
    {
        return $this->knocks;
    }

    public function admit(Stack $stack, Credential $said): Admitted
    {
        $this->knockedOn = $stack;
        $this->knocks++;

        // Read and dropped. The value is not carried anywhere — a fake holding
        // a password is the one place a test fixture could teach the habit
        // `N4-R5` exists to prevent — but it is read, because reading is what
        // spends it.
        $said->forTheExchange();

        return ($this->answer)();
    }
}
