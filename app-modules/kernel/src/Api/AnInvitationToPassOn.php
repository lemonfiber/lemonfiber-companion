<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function implode;
use function sprintf;
use function trim;

/**
 * An invitation put into words for the operator to pass on through the device's own sharing.
 *
 * A covering sentence this app writes, then the address exactly as the stack
 * sent it, then the stack's caution about that address where it has one. The
 * address and its caution are read off the {@see AnInvitationToHand} rather
 * than passed in, so the text cannot carry an address the stack did not give,
 * and the caution travels wherever the address goes.
 */
final readonly class AnInvitationToPassOn
{
    private function __construct(private AnInvitationToHand $toHand, private string $covering, private string $declining) {}

    /**
     * The invitation, under a covering sentence, with the sentence that leads to
     * the address turning it down; a blank sentence is refused.
     */
    public static function of(AnInvitationToHand $toHand, string $covering, string $declining): self
    {
        if (trim($covering) === '') {
            throw InvitationSaysNothing::about('covering');
        }

        if (trim($declining) === '') {
            throw InvitationSaysNothing::about('declining');
        }

        return new self($toHand, $covering, $declining);
    }

    /** What the sheet shows it as, which is the name it is for. */
    public function named(): string
    {
        return $this->toHand->name();
    }

    /**
     * The whole of what is handed over: the covering sentence, the address, its
     * caution, and the address that turns the invitation down, each where the
     * stack gave one.
     */
    public function text(): string
    {
        $address = $this->toHand->address();
        $parts = [$this->covering, $address->url()];

        if ($address->caution() !== '') {
            $parts[] = $address->caution();
        }

        if ($address->decline() !== '') {
            $parts[] = sprintf("%s\n%s", $this->declining, $address->decline());
        }

        return implode("\n\n", $parts);
    }
}
