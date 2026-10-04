<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function in_array;
use function trim;

/**
 * How one connection turned out, with what that state carries.
 *
 * Each constructor carries what its states say and nothing else, so a failure
 * cannot be built without the service's own words, a refusal or a skip without
 * the reason, and a conflict without lemonfiber's value beside the operator's.
 * The words are kept as the stack sent them: a service's rejection is what an
 * operator will search for, and a sentence of this app's own would hide it.
 */
final readonly class HowAConnectionEnded
{
    /** The states that carry nothing beside themselves. */
    private const array PLAIN = [
        WhereAConnectionStands::Wired,
        WhereAConnectionStands::AlreadyWired,
        WhereAConnectionStands::Drifted,
        WhereAConnectionStands::Stale,
        WhereAConnectionStands::Adopted,
        WhereAConnectionStands::Unmanaged,
        WhereAConnectionStands::WouldAdopt,
    ];

    /** The states that carry a reason. */
    private const array WITH_A_REASON = [
        WhereAConnectionStands::Observed,
        WhereAConnectionStands::Unmatched,
        WhereAConnectionStands::Skipped,
        WhereAConnectionStands::Refused,
    ];

    private function __construct(
        private WhereAConnectionStands $state,
        private string $said,
        private string $ours,
        private string $yours,
    ) {}

    /** A state that carries nothing beside itself. */
    public static function plainly(WhereAConnectionStands $state): self
    {
        if (! in_array($state, self::PLAIN, strict: true)) {
            throw TheWiringSaysNothing::wrongly($state);
        }

        return new self($state, '', '', '');
    }

    /** Observed, unmatched, skipped or refused, with the reason the stack gave; a blank reason is refused. */
    public static function because(WhereAConnectionStands $state, string $reason): self
    {
        if (! in_array($state, self::WITH_A_REASON, strict: true)) {
            throw TheWiringSaysNothing::wrongly($state);
        }

        if (trim($reason) === '') {
            throw TheWiringSaysNothing::about('reason');
        }

        return new self($state, $reason, '', '');
    }

    /** The service rejected it, in its own words; blank words are refused. */
    public static function failed(string $detail): self
    {
        if (trim($detail) === '') {
            throw TheWiringSaysNothing::about('detail');
        }

        return new self(WhereAConnectionStands::Failed, $detail, '', '');
    }

    /** Both moved: what lemonfiber would write, and what the service holds, `''` where it did not say. */
    public static function conflicted(string $ours, string $yours): self
    {
        if (trim($ours) === '') {
            throw TheWiringSaysNothing::about('ours');
        }

        return new self(WhereAConnectionStands::Conflicted, '', $ours, $yours);
    }

    /** A run that only said so: what it would write and what is there, each `''` where it did not say. */
    public static function wouldWire(string $ours, string $yours): self
    {
        return new self(WhereAConnectionStands::WouldWire, '', $ours, $yours);
    }

    /** Which of the thirteen it is. */
    public function state(): WhereAConnectionStands
    {
        return $this->state;
    }

    /** The reason, or the service's own words for a failure, or `''` for a state that carries none. */
    public function said(): string
    {
        return $this->said;
    }

    /** What lemonfiber would write, or `''`. */
    public function ours(): string
    {
        return $this->ours;
    }

    /** What the service holds, or `''`. */
    public function yours(): string
    {
        return $this->yours;
    }
}
