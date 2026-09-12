<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One machine this app has been introduced to.
 *
 * Everything that belongs to a stack is reached through one of these, which is
 * how `N1-R11`'s last clause becomes structural rather than careful: a reading
 * cannot be attributed to the wrong stack if the thing that holds it holds a
 * `StackId` too.
 *
 * **Four parts, and each is a rule.** The identity is not the address
 * (`N1-R22`), because a machine reached by another route is the same machine.
 * The name is what an operator picked, because "192.168.1.42" and
 * "192.168.1.43" are not two names. The address is where to dial and is treated
 * as private (`N1-R15`). The fingerprint is the certificate that machine
 * promised, checked on every connection whether or not the platform would
 * accept it (`ADR-0018`).
 *
 * **The session is deliberately not here.** `N1-R11` keeps each stack's session
 * separate and this would be the obvious place to keep it — which is exactly
 * why it is not: a stack is what the app remembers between launches, and a
 * session is what it may not (`N1-R23`, `N4-R5`). Putting them in one value
 * makes the thing that must be persisted and the thing that must not be the
 * same object, and the first piece of code to write one out takes the other
 * with it.
 */
final readonly class Stack
{
    private function __construct(
        private StackId $id,
        private string $name,
        private Address $at,
        private Fingerprint $presents,
    ) {}

    public static function of(StackId $id, string $name, Address $at, Fingerprint $presents): self
    {
        $called = trim($name);

        if ($called === '') {
            throw StackIsNotNamed::afterPairing();
        }

        return new self($id, $called, $at, $presents);
    }

    public function id(): StackId
    {
        return $this->id;
    }

    /** What the operator calls it. */
    public function name(): string
    {
        return $this->name;
    }

    public function at(): Address
    {
        return $this->at;
    }

    public function presents(): Fingerprint
    {
        return $this->presents;
    }

    public function is(self $other): bool
    {
        return $this->id->is($other->id);
    }
}
