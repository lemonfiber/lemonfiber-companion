<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\UpdateEnvelope;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\Release;
use Modules\Kernel\Api\Upkeep;
use Modules\Sdk\Internal\Changelogs;
use Modules\Sdk\Internal\Changes;
use Modules\Sdk\Internal\Endings;
use Modules\Sdk\Internal\Wire;

/**
 * The `update` envelope, read into what a screen can decide on.
 *
 * {@see Rosters} one endpoint over, and the same argument: the reading is a
 * separate thing from the port so that what a payload means is decided in one
 * place, and the adapter is left holding only the conversation.
 *
 * **Whether an update is waiting is the top-level `state`.** The stack compares
 * each service's running version with the one its build pins and answers
 * `updates-available` where any would move. The `changelog` beside it is the
 * release record, read by {@see Changelogs}: its own `state` says whether that
 * record matches the running build, and says nothing about updates.
 */
final readonly class Standings
{
    /**
     * @param Envelope<mixed> $envelope the `update` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): Upkeep
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw UpkeepIsUnreadable::missing(WireField::Data);
        }

        $pins = self::againstThePins($data);
        $changelog = Changelogs::in($data);
        $history = Changelogs::history($changelog);
        $changing = Changes::in($data);
        $stuck = Changes::permanentIn($data);
        $went = Endings::in($data);
        $inUse = Changelogs::running($changelog);

        return $inUse instanceof Release
            ? Upkeep::runningOn($pins, $inUse, $history, $changing, $stuck, $went)
            : Upkeep::reported($pins, $history, $changing, $stuck, $went);
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return UpdateEnvelope::in($envelope)->data;
    }

    /**
     * Where the services stand against their pins, as the stack said it.
     *
     * @param  array<array-key, mixed>  $data
     */
    private static function againstThePins(array $data): AgainstThePins
    {
        if (! array_key_exists(WireField::State->value, $data)) {
            throw UpkeepIsUnreadable::missing(WireField::State);
        }

        $said = $data[WireField::State->value];

        if (! is_string($said)) {
            throw UpkeepIsUnreadable::missing(WireField::State);
        }

        return AgainstThePins::tryFrom($said) ?? throw UpkeepIsUnreadable::state($said);
    }
}
