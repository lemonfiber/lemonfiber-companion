<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What the person being invited needs: the name they sign in as, one address, and how long it stands.
 *
 * The address is the stack's, held in an {@see AnAddressToHand} with the
 * stack's caution about it, and never built here. What happens when the hours
 * run out is withdrawal, and withdrawal removes the account.
 */
final readonly class AnInvitationToHand
{
    private function __construct(
        private string $name,
        private AnAddressToHand $address,
        private int $hours,
    ) {}

    /** The invitation as the stack gave it; a blank name, no address, or fewer hours than none is refused. */
    public static function to(string $name, AnAddressToHand $address, int $hours): self
    {
        if (trim($name) === '') {
            throw InvitationSaysNothing::about('name');
        }

        if ($address->url() === '') {
            throw InvitationSaysNothing::about('address');
        }

        if ($hours < 0) {
            throw InvitationSaysNothing::below('hours', $hours);
        }

        return new self($name, $address, $hours);
    }

    /** The name they sign in as. */
    public function name(): string
    {
        return $this->name;
    }

    /** The address to send them, exactly as the stack sent it, with its caution. */
    public function address(): AnAddressToHand
    {
        return $this->address;
    }

    /** How many hours it stands before it is withdrawn, counted from when it was offered. */
    public function hours(): int
    {
        return $this->hours;
    }
}
