<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\RepairEnvelope;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\LeftBehind;
use Modules\Kernel\Api\Mended;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\WhatBecameOfIt;
use Modules\Kernel\Api\WhatWasMended;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `repair` envelope, as the listing and as the record of what was done.
 *
 * Two folds in one class because they are two readings of one payload: what a
 * stack would put right, and what came of agreeing to it. The handle between
 * them is {@see Handles}' — the `job` envelope belongs to every action that
 * reaches the services rather than to this one.
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
     * What became of every repair in a listing the operator agreed to.
     *
     * The same envelope as {@see self::offerIn()}, read for its other half: the
     * contract carries `offered` and `mended` on one shape, and which of them
     * is worth reading is decided by what was asked rather than by anything on
     * the wire. So this is a second reader over one payload rather than a
     * branch inside the first — a reader that chose for itself would be
     * deciding whether the operator had agreed to anything, which is a fact
     * only the caller holds.
     *
     * @param Envelope<mixed> $envelope the `repair` envelope, as the job answered it
     */
    public static function mendedIn(Envelope $envelope): WhatWasMended
    {
        $data = self::listed(Wire::checked($envelope));

        if (! is_array($data)) {
            throw OfferIsUnreadable::missing(WireField::Data);
        }

        $rows = self::rows($data, WireField::Mended);
        $mended = [];
        $position = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw OfferIsUnreadable::outcome($position);
            }

            $mended[] = self::outcome($row, $position);
            $position++;
        }

        return WhatWasMended::of(...$mended);
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
     * One record: which repair, what became of it, and what it left.
     *
     * @param array<mixed> $row
     */
    private static function outcome(array $row, int $position): Mended
    {
        $about = self::under($row, WireField::Repair);

        if (! is_array($about)) {
            throw OfferIsUnreadable::outcome($position);
        }

        $repair = self::repair($about, $position);
        $outcome = self::under($row, WireField::Outcome);

        if (! is_array($outcome)) {
            throw OfferIsUnreadable::outcome($position);
        }

        $became = self::became($outcome, $position);

        return $became === WhatBecameOfIt::Stopped
            ? Mended::stopped($repair, self::left($outcome))
            : Mended::went($repair, $became);
    }

    /**
     * Which of the five this outcome is.
     *
     * The word is nested under its own key on the wire — `outcome.outcome` —
     * because the shape carries `leaving` beside it for the one case that has
     * something to leave. Refused rather than defaulted where it is a word this
     * app does not know: guessing is how a repair that overwrote nothing gets
     * shown as one that worked.
     *
     * @param array<mixed> $outcome
     */
    private static function became(array $outcome, int $position): WhatBecameOfIt
    {
        $said = self::under($outcome, WireField::Outcome);

        if (! is_string($said)) {
            throw OfferIsUnreadable::outcome($position);
        }

        return WhatBecameOfIt::tryFrom($said) ?? throw OfferIsUnreadable::word($said);
    }

    /**
     * What a stopped repair left, where it said.
     *
     * An absent `leaving` is {@see LeftBehind::nothing()} rather than a
     * refusal, which is the one optional field here with a real answer: a
     * repair that stopped and left nothing can be agreed to again without a
     * thought, and that is worth saying rather than refusing to say.
     *
     * @param array<mixed> $outcome
     */
    private static function left(array $outcome): LeftBehind
    {
        $said = self::under($outcome, WireField::Leaving);

        return is_string($said) && trim($said) !== '' ? LeftBehind::of($said) : LeftBehind::nothing();
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
     * One field of a row, whatever it holds, or `null` where it is absent.
     *
     * `C9` refuses `??` on a subscript because it reads as a default when it is
     * really an admission that nobody knows whether the key is there. Here
     * nobody does — the contract marks several of these optional — so the
     * absence is answered once, in one place, and every caller decides for
     * itself what it means. Most refuse; `leaving` answers
     * {@see LeftBehind::nothing()}, which is the distinction a shared `??`
     * would have flattened. The same helper {@see Households} carries, for the
     * same reason.
     *
     * @param array<mixed> $row
     */
    private static function under(array $row, WireField $field): mixed
    {
        // A guard rather than a ternary: rector rewrites
        // `array_key_exists(...) ? $row[...] : null` to `??`, which `C9` then
        // refuses. Both gates are right about what they see.
        if (! array_key_exists($field->value, $row)) {
            return null;
        }

        return $row[$field->value];
    }

    /**
     * A list under a named field.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
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

        // No `array_values` here, unlike a collection's own constructor: every
        // caller walks this with `foreach` and counts its own position, so the
        // keys never escape and reindexing is a line nothing could observe.
        // `Requested::of` and `Repairs::of` keep theirs because those arrays are
        // *stored* and handed out through an iterator. Walked, not kept.
        return $rows;
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
