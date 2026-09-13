<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\DoctorEnvelope;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Remedies;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Report;
use Modules\Kernel\Api\Severity;
use Modules\Kernel\Api\Standing;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Sdk\Internal\Wire;

/**
 * The `doctor` envelope, read as the report the health module works in.
 *
 * The other half of the seam `Problems` opened: `N1-R16` puts the client behind
 * this module, so this is where a diagnostic run stops being a shape the wire
 * describes and becomes one the screens do.
 *
 * **The verdict's payload is dropped, and that is the health module's decision
 * being carried out rather than restated.** On the wire a verdict is a tagged
 * union — `pass` may carry a note, `warn` and `fail` carry a whole problem,
 * `unverified` and `skipped` carry a reason. `Finding` takes the tag and
 * nothing else, because what a warning *said* is read differently on every
 * screen and there is no screen yet. A translation that quietly kept the
 * payload would fix that design before the thing it is for exists.
 *
 * `caused_by`, `said` and `service` go the same way, for the same reason and
 * with the same note in `Finding`.
 *
 * **A row it cannot read fails the whole report.** Half a report is the shape
 * that does damage: nine findings where ten ran reads as a stack with one fewer
 * problem, and nothing on the screen says a row was dropped. `Problems` refuses
 * a malformed remedy for the same reason, one size down.
 *
 * It parses rather than casts, and raises rather than refuses, exactly as
 * `Problems` does — the reasoning is written out there.
 */
final readonly class Reports
{
    /** @param Envelope<mixed> $envelope the `doctor` envelope, as the client returned it */
    public static function in(Envelope $envelope): Report
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw ReportIsUnreadable::missing(WireField::Data);
        }

        return Report::of(self::overall(self::text($data, WireField::Overall)), self::findings($data));
    }

    /**
     * The payload, as it actually arrived.
     *
     * Declared `mixed` deliberately, for the reason `Problems::payload` gives:
     * the generated envelope asserts its shape without checking it, and that
     * assertion is not a fact about the socket.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return DoctorEnvelope::in($envelope)->data;
    }

    /**
     * One field, as text, or a refusal naming it.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, WireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw ReportIsUnreadable::missing($field);
        }

        $said = $data[$field->value];

        if (! is_string($said)) {
            throw ReportIsUnreadable::missing($field);
        }

        return $said;
    }

    private static function overall(string $said): Overall
    {
        return Overall::tryFrom($said) ?? throw ReportIsUnreadable::overall($said);
    }

    /**
     * The findings, in the order the checks produced them.
     *
     * The order is information — two findings where one caused the other read
     * differently the other way round — so this preserves it and does nothing
     * else. Which order a person should read them in is `WorstFirst`'s
     * decision, made where there is a screen to make it for.
     *
     * @param array<mixed> $data
     */
    private static function findings(array $data): Findings
    {
        if (! array_key_exists(WireField::Findings->value, $data)) {
            throw ReportIsUnreadable::missing(WireField::Findings);
        }

        $rows = $data[WireField::Findings->value];

        if (! is_array($rows)) {
            throw ReportIsUnreadable::finding(0);
        }

        $findings = [];
        $position = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw ReportIsUnreadable::finding($position);
            }

            $findings[] = self::finding($row);
            $position++;
        }

        return Findings::of(...$findings);
    }

    /** @param array<mixed> $row */
    private static function finding(array $row): Finding
    {
        return Finding::of(
            Check::of(self::text($row, WireField::Check)),
            self::category(self::text($row, WireField::Category)),
            self::text($row, WireField::Title),
            self::conclusion(self::verdict($row)),
            self::said(self::verdict($row)),
        );
    }

    private static function category(string $said): Category
    {
        return Category::tryFrom($said) ?? throw ReportIsUnreadable::category($said);
    }

    /**
     * What the core said about the check, off the same verdict (`N2-R3`).
     *
     * The verdict is a union on the wire: the passing arm carries an optional
     * note, and every other arm carries a `code`, a `meaning` and a list of
     * `remedies`. All three are what `N2-R3` asks a finding to carry, and a
     * screen without them can say a check failed and nothing about what or what
     * to do.
     *
     * A missing `code` or `meaning` on a failing verdict is refused rather than
     * filled in, for the reason the rest of this reader refuses: a finding with
     * no sentence is a red row the operator cannot act on, and the core
     * producing one is a fault worth seeing here.
     *
     * Takes the verdict rather than the row, because {@see self::verdict()} has
     * already established that it is there and is an array. Checking again here
     * would be the same rule written twice, with the copy unreachable — which
     * is what `Pairing` learned when a sign check duplicated `Instant`'s.
     *
     * @param array<mixed> $verdict
     */
    private static function said(array $verdict): WhatTheCheckSaid
    {
        if (! array_key_exists(WireField::Code->value, $verdict)) {
            return WhatTheCheckSaid::nothingWrong();
        }

        return WhatTheCheckSaid::wentWrong(
            Code::of(self::text($verdict, WireField::Code)),
            self::text($verdict, WireField::Meaning),
            self::remedies($verdict),
            self::severity(self::text($verdict, WireField::Severity)),
            self::standing(self::text($verdict, WireField::State)),
        );
    }

    /**
     * How much a problem matters, as the engine judged it.
     *
     * Refused rather than defaulted where the word is not one this app knows:
     * a verdict whose severity reads as advisory because it could not be parsed
     * is a fault shown quietly, and quiet is the one thing a critical finding
     * must not be. `D4`'s argument, and {@see self::category()} makes the same
     * one about a check's family.
     *
     * @throws ReportIsUnreadable
     */
    private static function severity(string $said): Severity
    {
        return Severity::tryFrom($said) ?? throw ReportIsUnreadable::severity($said);
    }

    /**
     * Where a problem stands with respect to being fixed.
     *
     * Refused the same way and for a sharper reason: the distinction between
     * `Actionable` and `Guided` decides whether a screen offers a button, so a
     * word this app cannot read must not become the arm that offers one.
     *
     * @throws ReportIsUnreadable
     */
    private static function standing(string $said): Standing
    {
        return Standing::tryFrom($said) ?? throw ReportIsUnreadable::standing($said);
    }

    /**
     * The remedies the core offered, in its words.
     *
     * A verdict with no `remedies` key is a check the core had nothing to
     * suggest for, which is different from one it suggested nothing for — and
     * both read as no remedies, which is the honest answer either way.
     * `Remedies::likeliest()` is what a screen shows first.
     *
     * @param array<mixed> $verdict
     */
    private static function remedies(array $verdict): Remedies
    {
        if (! array_key_exists(WireField::Remedies->value, $verdict)) {
            return Remedies::none();
        }

        $offered = $verdict[WireField::Remedies->value];

        if (! is_array($offered)) {
            throw ReportIsUnreadable::missing(WireField::Remedies);
        }

        $read = [];

        foreach ($offered as $one) {
            if (! is_array($one)) {
                throw ReportIsUnreadable::missing(WireField::Remedies);
            }

            $read[] = Remedy::of(self::text($one, WireField::Action));
        }

        return Remedies::of(...$read);
    }

    /**
     * The verdict off a finding row, established once.
     *
     * Both readers below need it, and it is found here so that neither finds it
     * again. A second check on the same value would be unreachable anyway —
     * arguments evaluate left to right, so the first reader refuses anything
     * the second would have caught — and unreachable checks are the ones no
     * test can defend.
     *
     * Written out rather than coalesced, which `C9` refuses by name: a `??` on
     * a subscript folds absent, present-and-null and present-and-wrong-type
     * into one answer, and the one it picks reads as "carry on".
     *
     * @param  array<mixed>  $row
     * @return array<mixed>
     */
    private static function verdict(array $row): array
    {
        if (! array_key_exists(WireField::Verdict->value, $row)) {
            throw ReportIsUnreadable::missing(WireField::Verdict);
        }

        $verdict = $row[WireField::Verdict->value];

        if (! is_array($verdict)) {
            throw ReportIsUnreadable::missing(WireField::Verdict);
        }

        return $verdict;
    }

    /**
     * The tag off the verdict, which is all this one reads.
     *
     * What the verdict also carries — the code, the meaning, the remedies — is
     * read by {@see self::said()}, which is where `N2-R3` is answered.
     *
     * @param array<mixed> $verdict
     */
    private static function conclusion(array $verdict): Conclusion
    {
        $said = self::text($verdict, WireField::Outcome);

        return Conclusion::tryFrom($said) ?? throw ReportIsUnreadable::outcome($said);
    }
}
