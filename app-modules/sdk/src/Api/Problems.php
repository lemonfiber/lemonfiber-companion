<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ErrorEnvelope;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Problem;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Sdk\Internal\Wire;

/**
 * The `error` envelope, read as the kernel's `Problem`.
 *
 * This is the seam the whole design turns on. `Problem` is the shape every
 * screen in this app works in, and it is deliberately not the wire type —
 * `N1-R16` puts the SDK behind this module and nothing else may name it. So
 * one direction of that translation lives here, and it is the only place the
 * two vocabularies are held against each other.
 *
 * **It parses rather than casts.** The generated envelope declares its payload's
 * shape and asserts it without checking — which is correct for generated code,
 * because the contract is the authority and a client that re-validated it
 * would be a second opinion about the same document. What actually arrives is
 * whatever came off a socket, from a server that may be newer than this app.
 * So every field is read, checked, and refused where it is not what it claims
 * (C9): the alternative is a `Severity` that came out of a cast and a screen
 * that shows a critical failure as an advisory.
 *
 * **It raises rather than refuses, and that is not a breach of C1.** C1 is
 * about a refusal crossing a module boundary — something an operator acts on —
 * and `ProblemIsUnreadable` is not one: it says the answer was not an error in
 * the shape the contract describes, which is a fault in a server, a proxy or a
 * fixture. There is no `Problem` to hand back, because being unable to read one
 * is the thing that happened. It is raised without an `@throws` for the reason
 * `CodeIsBlank` gives: a caller has nothing to do differently, and marking it
 * checked would put a catch block at every call site that could only rethrow.
 *
 * **The two the kernel drops are dropped here too.** `detail` and `cause` are
 * on the wire and absent from `Problem`, and this is where that decision is
 * carried out rather than restated: a translation that quietly kept them would
 * make the kernel's docblock wrong and nothing would fail.
 */
final readonly class Problems
{
    /** @param Envelope<mixed> $envelope the `error` envelope, as the client returned it */
    public static function in(Envelope $envelope): Problem
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ProblemIsUnreadable::missing(WireField::Data);
        }

        return Problem::of(
            Code::of(self::text($data, WireField::Code)),
            self::severity(self::text($data, WireField::Severity)),
            self::standing(self::text($data, WireField::State)),
            self::text($data, WireField::Summary),
            self::text($data, WireField::Meaning),
            self::remedies($data),
        );
    }

    /**
     * The payload, as it actually arrived.
     *
     * Declared `mixed` deliberately. `ErrorEnvelope::in` holds the kind on the
     * wire against the kind being read for — the client's own business, so its
     * `UnexpectedKind` is left to travel — and then asserts the payload's shape
     * without checking it. That assertion is correct for generated code and it
     * is not a fact about the socket, so the type is widened back to what is
     * actually known here. Otherwise the checks below are lines the analyser
     * calls redundant and the next reader deletes.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return ErrorEnvelope::in($envelope)->data;
    }

    /**
     * One field, as text, or a refusal naming it.
     *
     * `array_key_exists` and `is_string` rather than a coalesce: `??` on an
     * array read turns a missing field and a field holding null into the same
     * thing, and the whole point here is to say which arrived.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, WireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw ProblemIsUnreadable::missing($field);
        }

        $said = $data[$field->value];

        if (! is_string($said)) {
            throw ProblemIsUnreadable::missing($field);
        }

        return $said;
    }

    private static function severity(string $said): Severity
    {
        return Severity::tryFrom($said) ?? throw ProblemIsUnreadable::severity($said);
    }

    private static function standing(string $said): Standing
    {
        return Standing::tryFrom($said) ?? throw ProblemIsUnreadable::standing($said);
    }

    /**
     * The remedies, in the order the server put them in.
     *
     * The order is the server's judgement of what is most likely to work, and
     * `Remedies` says a screen re-sorting them discards the one thing it cannot
     * work out for itself. So this preserves it and does nothing else.
     *
     * An absent list is an empty one rather than a refusal: `Remedies::none()`
     * is a legitimate value, and `Standing::Unknown` is what says a problem has
     * no known remedy.
     *
     * @param array<mixed> $data
     */
    private static function remedies(array $data): Remedies
    {
        if (! array_key_exists(WireField::Remedies->value, $data)) {
            return Remedies::none();
        }

        $rows = $data[WireField::Remedies->value];

        if (! is_array($rows)) {
            throw ProblemIsUnreadable::remedy(0);
        }

        $remedies = [];
        $position = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw ProblemIsUnreadable::remedy($position);
            }

            $remedies[] = Remedy::of(self::text($row, WireField::Action));
            $position++;
        }

        return Remedies::of(...$remedies);
    }
}
