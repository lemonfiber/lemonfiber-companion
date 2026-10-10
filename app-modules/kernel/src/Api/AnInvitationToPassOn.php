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
 * sent it, then the stack's caution about that address where it has one, then
 * its join link and the address that turns it down, each under a sentence of
 * this app's, where the stack gave them. The
 * address and its caution are read off the {@see AnInvitationToHand} rather
 * than passed in, so the text cannot carry an address the stack did not give,
 * and the caution travels wherever the address goes.
 */
final readonly class AnInvitationToPassOn
{
    private function __construct(private AnInvitationToHand $toHand, private string $covering, private string $joining, private string $declining) {}

    /**
     * The invitation, under a covering sentence, with the sentences that lead to
     * its join link and to the address turning it down; a blank sentence is refused.
     */
    public static function of(AnInvitationToHand $toHand, string $covering, string $joining, string $declining): self
    {
        foreach (['covering' => $covering, 'joining' => $joining, 'declining' => $declining] as $which => $sentence) {
            if (trim($sentence) === '') {
                throw InvitationSaysNothing::about($which);
            }
        }

        return new self($toHand, $covering, $joining, $declining);
    }

    /** What the sheet shows it as, which is the name it is for. */
    public function named(): string
    {
        return $this->toHand->name();
    }

    /**
     * The whole of what is handed over: the covering sentence, the address, its
     * caution, its join link, and the address that turns the invitation down,
     * each where the stack gave one.
     */
    public function text(): string
    {
        $address = $this->toHand->address();
        $parts = [$this->covering, $address->url()];

        if ($address->caution() !== '') {
            $parts[] = $address->caution();
        }

        if ($address->join() !== '') {
            $parts[] = sprintf("%s\n%s", $this->joining, $address->join());
        }

        if ($address->decline() !== '') {
            $parts[] = sprintf("%s\n%s", $this->declining, $address->decline());
        }

        return implode("\n\n", $parts);
    }
}
