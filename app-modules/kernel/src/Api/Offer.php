<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One listing of what a stack would put right, and the name it goes by.
 *
 * The repairs and the word identifying the listing travel together because
 * they need to. A confirmation quotes the listing it is answering, and
 * the engine refuses it where the machine has moved since — so a screen holding
 * repairs without the word could only say yes in the abstract, which the engine
 * reads as standing consent, which this surface is forbidden from
 * sending.
 *
 * **The word is opaque and stays opaque.** It is the engine's name for a moment
 * in time, not something an operator reads and not something this app parses.
 * Nothing here inspects it; {@see Confirmed} carries it back out and the port
 * that acts hands it over. A type that let a screen read it
 * would be inviting somebody to make a decision from it, and every decision
 * that could be made from it is the engine's.
 *
 * **Blank is refused.** An offer with no name is one no confirmation can quote,
 * so a screen built on it could only ever send the consent this app must never
 * send. Refused here rather than discovered there, which is {@see Repair}'s
 * argument about a blank `does` one level up.
 */
final readonly class Offer
{
    private function __construct(private string $named, private Repairs $repairs) {}

    public static function of(string $named, Repairs $repairs): self
    {
        $word = trim($named);

        if ($word === '') {
            throw OfferHasNoName::fromTheStack();
        }

        return new self($word, $repairs);
    }

    /**
     * What the engine calls this listing, for quoting back with a yes.
     *
     * Published because {@see Confirmed} has to carry it, and for nothing else.
     * See the class docblock for why no screen should read it.
     */
    public function named(): string
    {
        return $this->named;
    }

    /** What the stack would put right, in the order it offered them. */
    public function repairs(): Repairs
    {
        return $this->repairs;
    }
}
