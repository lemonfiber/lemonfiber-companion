<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_values;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\JobEnvelope;
use Lemonfiber\Sdk\Generated\RepairEnvelope;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Undoing;
use Modules\Sdk\Internal\Wire;

/**
 * The `job` and `repair` envelopes, as the handle and the listing.
 *
 * Two folds in one class because they are two halves of one exchange: asking
 * what a stack would put right answers a handle, and the handle answers the
 * listing. Splitting them would put the two readings of a single round trip in
 * different files with nothing saying they belong together.
 *
 * Written the way {@see Reports} and {@see Households} are — static, reading
 * through {@see WireField} so no field name is spelled twice, and refusing
 * rather than salvaging.
 *
 * **`reversible: bool` becomes {@see Undoing}, deliberately.** The wire says
 * true or false and the app says permanent or possible, because a boolean at a
 * call site is a coin toss about which way round it reads — and this is the one
 * field on this screen where reading it backwards means telling somebody a
 * thing can be undone when it cannot.
 */
final readonly class Offers
{
    /**
     * The handle a stack answered an action with.
     *
     * @param Envelope<mixed> $envelope the `job` envelope, as the client returned it
     */
    public static function handleIn(Envelope $envelope): Job
    {
        $data = self::handedBack(Wire::checked($envelope));

        if (! is_array($data)) {
            throw OfferIsUnreadable::missing(WireField::Data);
        }

        return Job::named(self::text($data, WireField::Job));
    }

    /**
     * What a stack said it would put right, and the name of the listing.
     *
     * The name travels with the repairs rather than beside them because
     * `N2-R6` has a yes quote the listing it was given: an {@see Offer} that
     * could be built without the name would be one a confirmation could not
     * quote, and the failure would appear at the moment of agreeing.
     *
     * @param Envelope<mixed> $envelope the `repair` envelope, as the job answered it
     */
    public static function offerIn(Envelope $envelope): Offer
    {
        $data = self::listed(Wire::checked($envelope));

        if (! is_array($data)) {
            throw OfferIsUnreadable::missing(WireField::Data);
        }

        return Offer::of(self::text($data, WireField::Agreement), self::repairs($data));
    }

    /**
     * The `job` payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Reports::payload()}'s reason: the
     * generated envelope asserts its shape without checking it, and that
     * assertion is not a fact about the socket.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function handedBack(Envelope $envelope): mixed
    {
        return JobEnvelope::in($envelope)->data;
    }

    /**
     * The `repair` payload, as it actually arrived. Same argument as above.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function listed(Envelope $envelope): mixed
    {
        return RepairEnvelope::in($envelope)->data;
    }

    /**
     * Every repair the listing holds, in the order the stack offered them.
     *
     * The order is the stack's and is preserved untouched, for {@see Reports}'
     * reason: which order a person should read them in is a screen's decision.
     *
     * @param array<mixed> $data
     */
    private static function repairs(array $data): Repairs
    {
        $rows = self::rows($data, WireField::Offered);
        $repairs = [];
        $position = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw OfferIsUnreadable::repair($position);
            }

            $repairs[] = self::repair($row, $position);
            $position++;
        }

        return Repairs::of(...$repairs);
    }

    /**
     * One repair, with all three of `N2-R4`'s clauses or none of it.
     *
     * @param array<mixed> $row
     */
    private static function repair(array $row, int $position): Repair
    {
        return Repair::offered(
            self::said($row, WireField::Check, $position),
            self::said($row, WireField::Does, $position),
            Effects::of(...self::effects($row, $position)),
            self::undoing($row, $position),
        );
    }

    /**
     * What else this repair touches.
     *
     * An empty list is {@see Effects::nothingElse()} by construction rather
     * than by a branch here: a repair that affects nothing is an ordinary
     * repair, and the variadic says so without this file deciding it.
     *
     * @param  array<mixed> $row
     * @return list<string>
     */
    private static function effects(array $row, int $position): array
    {
        // Its own presence check rather than `rows()`, and the difference is
        // the message: `rows()` raises *this envelope has no `effects`*, which
        // is true of an envelope and false of a repair. A listing of six whose
        // fourth is missing its consequences would have reported as an answer
        // from an unreadable version of lemonfiber, and the position — the one
        // thing that makes it findable — would have been dropped.
        if (! array_key_exists(WireField::Effects->value, $row)) {
            throw OfferIsUnreadable::repair($position);
        }

        $effects = $row[WireField::Effects->value];

        if (! is_array($effects)) {
            throw OfferIsUnreadable::repair($position);
        }

        $said = [];

        foreach ($effects as $one) {
            if (! is_string($one)) {
                throw OfferIsUnreadable::repair($position);
            }

            $said[] = $one;
        }

        return $said;
    }

    /**
     * Whether it can be taken back, as the word rather than the boolean.
     *
     * Absent is refused rather than assumed either way. `N2-R4` requires the
     * app to state this, and a repair that arrived without it is one this app
     * cannot make the required statement about — guessing *permanent* would
     * frighten somebody off a reversible fix, and guessing *possible* would
     * tell them a permanent one can be undone.
     *
     * @param array<mixed> $row
     */
    private static function undoing(array $row, int $position): Undoing
    {
        if (! array_key_exists(WireField::Reversible->value, $row)) {
            throw OfferIsUnreadable::repair($position);
        }

        $said = $row[WireField::Reversible->value];

        if (! is_bool($said)) {
            throw OfferIsUnreadable::repair($position);
        }

        return $said ? Undoing::Possible : Undoing::Permanent;
    }

    /**
     * A list under a named field.
     *
     * @param  array<mixed> $data
     * @return list<mixed>
     */
    private static function rows(array $data, WireField $field): array
    {
        if (! array_key_exists($field->value, $data)) {
            throw OfferIsUnreadable::missing($field);
        }

        $rows = $data[$field->value];

        if (! is_array($rows)) {
            throw OfferIsUnreadable::missing($field);
        }

        return array_values($rows);
    }

    /**
     * One field of an envelope, as text.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, WireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw OfferIsUnreadable::missing($field);
        }

        $said = $data[$field->value];

        if (! is_string($said)) {
            throw OfferIsUnreadable::missing($field);
        }

        return $said;
    }

    /**
     * One field of a repair, as text, named by which repair it was missing from.
     *
     * Separate from {@see text()} because the refusal is different: a missing
     * `agreement` is an envelope this app cannot read at all, and a missing
     * `does` is one repair in a listing — and the position is what makes the
     * second one findable.
     *
     * @param array<mixed> $row
     */
    private static function said(array $row, WireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row)) {
            throw OfferIsUnreadable::repair($position);
        }

        $said = $row[$field->value];

        if (! is_string($said)) {
            throw OfferIsUnreadable::repair($position);
        }

        return $said;
    }
}
