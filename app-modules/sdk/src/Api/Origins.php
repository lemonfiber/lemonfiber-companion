<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ProvenanceEnvelope;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhereItComesFrom;
use Modules\Kernel\Api\WhereTheServicesComeFrom;
use Modules\Sdk\Api\Fields\ProvenanceField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `provenance` envelope, as where this app can say each service comes from.
 *
 * The sibling of {@see Records} for what a stack runs rather than what it did,
 * written the same way: a static fold with no state, reading through
 * {@see WireField} so no field name is spelled twice, and refusing rather than
 * salvaging.
 *
 * **Everything the types one layer down would refuse is refused here first**,
 * as {@see ProvenanceIsUnreadable}, with the row's position — a blank word, a
 * service named twice. The kernel's own refusals are the backstop: the adapter
 * turns this class's refusal into an obstacle, and one that reached a kernel
 * constructor would surface as an uncaught raise on a screen instead.
 *
 * **The rows keep the stack's order**, which is the order it declares them in.
 */
final readonly class Origins
{
    /**
     * Where every service a stack declares comes from.
     *
     * @param Envelope<mixed> $envelope the `provenance` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhereTheServicesComeFrom
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ProvenanceIsUnreadable::missing(WireField::Data);
        }

        return WhereTheServicesComeFrom::declaring(...self::services($data));
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason: the
     * generated envelope asserts its shape without checking it.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return ProvenanceEnvelope::in($envelope)->data;
    }

    /**
     * Every service, refusing any row this app cannot show.
     *
     * @param  array<mixed>           $data
     * @return list<WhereItComesFrom>
     */
    private static function services(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data) as $row) {
            if (! is_array($row)) {
                throw ProvenanceIsUnreadable::service($position);
            }

            $origin = self::one($row, $position);
            self::refuseASecond($origin, $found, $position);
            $found[] = $origin;
            $position++;
        }

        return $found;
    }

    /**
     * The rows, as they arrived.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data): array
    {
        if (! array_key_exists(WireField::Services->value, $data)) {
            throw ProvenanceIsUnreadable::missing(WireField::Services);
        }

        $rows = $data[WireField::Services->value];

        if (! is_array($rows)) {
            throw ProvenanceIsUnreadable::missing(WireField::Services);
        }

        return $rows;
    }

    /**
     * Refuse a service already declared, by the kernel's own idea of *the same*.
     *
     * Compared as {@see ServiceId}s rather than as the strings that arrived,
     * because the identity trims: ` sonarr` and `sonarr` are one service, and
     * a check on the raw text would pass them both to a kernel that refuses
     * the second as an uncaught raise.
     *
     * @param list<WhereItComesFrom> $earlier
     */
    private static function refuseASecond(WhereItComesFrom $origin, array $earlier, int $position): void
    {
        foreach ($earlier as $before) {
            if ($before->service()->isTheSameAs($origin->service())) {
                throw ProvenanceIsUnreadable::twice($origin->service()->named(), $position);
            }
        }
    }

    /**
     * One row, as where the service it names comes from.
     *
     * @param array<mixed> $row
     */
    private static function one(array $row, int $position): WhereItComesFrom
    {
        return WhereItComesFrom::declared(
            ServiceId::called(self::text($row, WireField::Id, $position)),
            self::text($row, WireField::Name, $position),
            self::text($row, ProvenanceField::Image, $position),
            self::text($row, ProvenanceField::Pinned, $position),
            self::text($row, ProvenanceField::Upstream, $position),
            self::text($row, ProvenanceField::License, $position),
        );
    }

    /**
     * A named field of one row, as text an operator can be shown.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $field, int $position): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $row)) {
            throw ProvenanceIsUnreadable::said($field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw ProvenanceIsUnreadable::said($field, $position);
        }

        return $said;
    }
}
