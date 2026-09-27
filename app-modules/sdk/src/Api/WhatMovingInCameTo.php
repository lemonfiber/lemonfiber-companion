<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\AdoptionEnvelope;
use Lemonfiber\Sdk\Generated\BesideEnvelope;
use Lemonfiber\Sdk\Generated\ImportEnvelope;
use Lemonfiber\Sdk\Generated\ReplacementEnvelope;
use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\APortMoved;
use Modules\Kernel\Api\ARecord;
use Modules\Kernel\Api\AServiceAdopted;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheImport;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheRecords;
use Modules\Kernel\Api\TheReplacement;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatAdoptingOneWouldDo;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Sdk\Api\Fields\AdoptionField;
use Modules\Sdk\Api\Fields\BesideField;
use Modules\Sdk\Api\Fields\ImportField;
use Modules\Sdk\Api\Fields\ReplacementField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\WhatAMoveCarries;
use Modules\Sdk\Internal\Wire;

/**
 * Reads what one way of moving in came to, out of whichever of the four envelopes the stack answered with.
 *
 * Written the way {@see WhatIsAlreadyHere} is: a static fold with no state,
 * refusing anything the kernel would refuse, with {@see MoveIsUnreadable}.
 * Every list keeps the stack's order.
 *
 * **Which way it was is the envelope's kind.** Each act answers with its own
 * envelope, so the kind decides which is read — not the act that was asked
 * for, which would be this app deciding what the stack answers with. A kind
 * that is none of the four is refused by the last envelope's own reader.
 */
final readonly class WhatMovingInCameTo
{
    /**
     * Where the move stands, and what it came to or would come to.
     *
     * @param Envelope<mixed> $envelope the envelope the finished work answered with
     */
    public static function in(Envelope $envelope): AMove
    {
        $checked = Wire::checked($envelope);

        return match ($checked->kind) {
            AdoptionEnvelope::KIND->value => self::adopting($checked),
            ImportEnvelope::KIND->value => self::importing($checked),
            BesideEnvelope::KIND->value => self::standingBeside($checked),
            default => self::replacing($checked),
        };
    }

    /**
     * What adopting came to: each service a newer version would open, and the copy taken first.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function adopting(Envelope $envelope): AMove
    {
        $kind = AdoptionEnvelope::KIND->value;
        $data = self::adoption($envelope);

        if (! is_array($data)) {
            throw MoveIsUnreadable::missing($kind, WireField::Data);
        }

        return WhatAMoveCarries::came($data, $kind, TheAdoption::of(
            WhatAMoveCarries::optional($data, WireField::Project, $kind),
            WhatAMoveCarries::named($data, AdoptionField::BackUp, $kind),
            WhatAMoveCarries::optional($data, AdoptionField::BackedUp, $kind),
            ...self::upgrades($data),
        ));
    }

    /**
     * What importing came to: what was carried, what would be, and what could not be.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function importing(Envelope $envelope): AMove
    {
        $kind = ImportEnvelope::KIND->value;
        $data = self::import($envelope);

        if (! is_array($data)) {
            throw MoveIsUnreadable::missing($kind, WireField::Data);
        }

        return WhatAMoveCarries::came($data, $kind, TheImport::of(
            WhatAMoveCarries::optional($data, WireField::Project, $kind),
            TheRecords::of(...self::records($data, ImportField::Carried)),
            TheRecords::of(...self::records($data, ImportField::WouldCarry)),
            WhatIsUnsupported::these(...self::notCarried($data)),
        ));
    }

    /**
     * What standing beside came to: where each service listens instead, and the file that says so.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function standingBeside(Envelope $envelope): AMove
    {
        $kind = BesideEnvelope::KIND->value;
        $data = self::beside($envelope);

        if (! is_array($data)) {
            throw MoveIsUnreadable::missing($kind, WireField::Data);
        }

        return WhatAMoveCarries::came($data, $kind, TheStandingBeside::of(
            ThePortsMoved::of(...self::ports($data)),
            WhatAMoveCarries::optional($data, BesideField::Written, $kind),
        ));
    }

    /**
     * What replacing came to: what it would stop, what it stopped, and what would not stop.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function replacing(Envelope $envelope): AMove
    {
        $kind = ReplacementEnvelope::KIND->value;
        $data = self::replacement($envelope);

        if (! is_array($data)) {
            throw MoveIsUnreadable::missing($kind, WireField::Data);
        }

        return WhatAMoveCarries::came($data, $kind, TheReplacement::of(
            WhatAMoveCarries::optional($data, WireField::Project, $kind),
            WhatAMoveCarries::named($data, ReplacementField::WouldStop, $kind),
            WhatAMoveCarries::named($data, WireField::Stopped, $kind),
            WhatAMoveCarries::named($data, ReplacementField::StillRunning, $kind),
        ));
    }

    /**
     * Each service a newer version would open, with both versions and what the survey said of it.
     *
     * @param  array<mixed>          $data
     * @return list<AServiceAdopted>
     */
    private static function upgrades(array $data): array
    {
        $kind = AdoptionEnvelope::KIND->value;
        $list = AdoptionField::Upgrades;
        $found = [];
        $position = 0;

        foreach (Required::rows($data, $list, MoveIsUnreadable::missing($kind, $list)) as $row) {
            if (! is_array($row)) {
                throw MoveIsUnreadable::entry($kind, $list, $position, WireField::Service);
            }

            $found[] = AServiceAdopted::said(
                WhatAdoptingOneWouldDo::said(
                    Required::text($row, WireField::Service, MoveIsUnreadable::entry($kind, $list, $position, WireField::Service)),
                    Required::text($row, WireField::Because, MoveIsUnreadable::entry($kind, $list, $position, WireField::Because)),
                    Required::flag($row, WireField::BackupFirst, MoveIsUnreadable::entry($kind, $list, $position, WireField::BackupFirst)),
                    Required::flag($row, WireField::Refused, MoveIsUnreadable::entry($kind, $list, $position, WireField::Refused)),
                ),
                Required::text($row, AdoptionField::Existing, MoveIsUnreadable::entry($kind, $list, $position, AdoptionField::Existing)),
                Required::text($row, WireField::Ours, MoveIsUnreadable::entry($kind, $list, $position, WireField::Ours)),
                Required::text($row, WireField::Verdict, MoveIsUnreadable::entry($kind, $list, $position, WireField::Verdict)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * The records under one list of an import, each by its service, kind and name.
     *
     * @param  array<mixed>  $data
     * @return list<ARecord>
     */
    private static function records(array $data, NamesAWireField $list): array
    {
        $kind = ImportEnvelope::KIND->value;
        $found = [];
        $position = 0;

        foreach (Required::rows($data, $list, MoveIsUnreadable::missing($kind, $list)) as $row) {
            if (! is_array($row)) {
                throw MoveIsUnreadable::entry($kind, $list, $position, WireField::Service);
            }

            $found[] = ARecord::of(
                Required::text($row, WireField::Service, MoveIsUnreadable::entry($kind, $list, $position, WireField::Service)),
                Required::text($row, WireField::Kind, MoveIsUnreadable::entry($kind, $list, $position, WireField::Kind)),
                Required::text($row, WireField::Name, MoveIsUnreadable::entry($kind, $list, $position, WireField::Name)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What an import could not carry, each with why.
     *
     * @param  array<mixed>      $data
     * @return list<Unsupported>
     */
    private static function notCarried(array $data): array
    {
        $kind = ImportEnvelope::KIND->value;
        $list = WireField::NotCarried;
        $found = [];
        $position = 0;

        foreach (Required::rows($data, $list, MoveIsUnreadable::missing($kind, $list)) as $row) {
            if (! is_array($row)) {
                throw MoveIsUnreadable::entry($kind, $list, $position, WireField::What);
            }

            $found[] = Unsupported::of(
                Required::text($row, WireField::What, MoveIsUnreadable::entry($kind, $list, $position, WireField::What)),
                Required::text($row, WireField::Because, MoveIsUnreadable::entry($kind, $list, $position, WireField::Because)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * Where each service would listen to stand beside what is here.
     *
     * @param  array<mixed>     $data
     * @return list<APortMoved>
     */
    private static function ports(array $data): array
    {
        $kind = BesideEnvelope::KIND->value;
        $list = WireField::Ports;
        $found = [];
        $position = 0;

        foreach (Required::rows($data, $list, MoveIsUnreadable::missing($kind, $list)) as $row) {
            if (! is_array($row)) {
                throw MoveIsUnreadable::entry($kind, $list, $position, WireField::Service);
            }

            $found[] = APortMoved::of(
                Required::text($row, WireField::Service, MoveIsUnreadable::entry($kind, $list, $position, WireField::Service)),
                Required::number($row, WireField::From, MoveIsUnreadable::entry($kind, $list, $position, WireField::From)),
                Required::number($row, WireField::To, MoveIsUnreadable::entry($kind, $list, $position, WireField::To)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * The `adoption` payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function adoption(Envelope $envelope): mixed
    {
        return AdoptionEnvelope::in($envelope)->data;
    }

    /**
     * The `import` payload, as it actually arrived.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function import(Envelope $envelope): mixed
    {
        return ImportEnvelope::in($envelope)->data;
    }

    /**
     * The `beside` payload, as it actually arrived.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function beside(Envelope $envelope): mixed
    {
        return BesideEnvelope::in($envelope)->data;
    }

    /**
     * The `replacement` payload, as it actually arrived.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function replacement(Envelope $envelope): mixed
    {
        return ReplacementEnvelope::in($envelope)->data;
    }
}
