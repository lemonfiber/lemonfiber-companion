<?php

declare(strict_types=1);

namespace Modules\Dx\Api;

use Modules\Dx\Adapters\TheStoreThisRunKeeps;
use Modules\Kernel\Api\Standings;
use Modules\Vault\Api\PlatformStandings;

/**
 * The word each stack's one line last said, kept for one run.
 *
 * The third port sitting on the device's store, and it is here for the reason
 * the other two are: with stand-ins on, what gets written under it is a word
 * about a machine that does not exist, attributed to a stack id nothing else
 * will ever use. Left to the real keychain that is a row an operator's device
 * carries for good, about a stack they were never introduced to.
 *
 * Credentials, sessions and pairing material are named rather than
 * this word, so this is not the requirement's letter — it is the same sentence
 * about the same store, and the line between *material* and *residue* is not
 * one worth arguing over when closing it costs a class that says what the other
 * two say.
 *
 * @implements StandsIn<Standings>
 */
final readonly class StandingsThisRunKeeps implements StandsIn
{
    public function __construct(private TheStoreThisRunKeeps $store) {}

    public function insteadOf(): string
    {
        return Standings::class;
    }

    public function which(): Standings
    {
        return new PlatformStandings($this->store);
    }
}
