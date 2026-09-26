<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\DoctorEnvelope;
use Modules\Kernel\Api\AboutWhat;
use Modules\Kernel\Api\Because;
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
use Modules\Kernel\Api\WhatItSaysUnderneath;
use Modules\Kernel\Api\WhatTheCheckSaid;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Sdk\Api\Fields\DoctorField;
use Modules\Sdk\Internal\Attributions;
use Modules\Sdk\Internal\Wire;

/**
 * The `doctor` envelope, read as the report the health module works in.
 *
 * The other half of the seam `Problems` opened: the client sits behind
 * this module, so this is where a diagnostic run stops being a shape the wire
 * describes and becomes one the screens do.
 *
 * **The verdict is a tagged union, and every arm's words are read.** `pass` may
 * carry a note, `warn` and `fail` carry a whole problem, `unverified` and
 * `skipped` carry a reason. This once took the tag and nothing else, on the
 * argument that what a warning *said* is read differently on every screen and
 * there was no screen yet. `HowThisStackIs` is that screen, and the argument
 * expired with it: the words crossed the wire and stopped here, so an operator
 * saw that a check failed and nothing about what or what to do.
 *
 * The `pass` note is still dropped, and deliberately — the rule is about what a
 * finding must carry when something is wrong, and a note on a passing check is
 * the core being chatty. `caused_by`, `said` and `service` go the same way,
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

        return Report::of(self::overall(self::text($data, DoctorField::Overall)), self::findings($data));
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
    private static function text(array $data, NamesAWireField $field): string
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

    /**
     * One field of one finding, as text, or a refusal naming which finding.
     *
     * Beside {@see text()} rather than replacing it, because the two describe
     * different failures: an envelope with no `overall` is an envelope this app
     * cannot read, and a finding with no `title` is one row of a report. Saying
     * the second in the first's words — *the doctor envelope has no `title`* —
     * is both untrue and unhelpful, since a report of nine findings gives a
     * reader nothing to look at.
     *
     * @param array<mixed> $row
     */
    private static function saidIn(array $row, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row)) {
            throw ReportIsUnreadable::inFinding($position, $field);
        }

        $said = $row[$field->value];

        if (! is_string($said)) {
            throw ReportIsUnreadable::inFinding($position, $field);
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

            $findings[] = self::finding($row, $position);
            $position++;
        }

        return Findings::of(...$findings);
    }

    /** @param array<mixed> $row */
    private static function finding(array $row, int $position): Finding
    {
        $verdict = self::verdict($row, $position);
        $conclusion = self::conclusion($verdict, $position);

        $finding = Finding::of(
            Check::of(self::saidIn($row, WireField::Check, $position)),
            self::category(self::saidIn($row, WireField::Category, $position)),
            self::saidIn($row, WireField::Title, $position),
            $conclusion,
            self::said($verdict, $conclusion, $position),
            self::origin($row, $position),
        );

        // Set after the row is complete, which is how the engine sets them:
        // neither is the check's own business, and a finding that names neither
        // is the ordinary case rather than an incomplete one.
        return $finding
            ->about(self::about($row, $position))
            ->because(self::because($row, $position));
    }

    /**
     * Which service the row says it is about, where it says.
     *
     * Absent for a check about the machine rather than about something running
     * on it, which is why this reads as an arm and not as a refusal. Present
     * and blank *is* refused — {@see AboutWhat::theService()} does that — since
     * a row claiming a service and naming none has a fault worth seeing here.
     *
     * @param array<mixed> $row
     */
    private static function about(array $row, int $position): AboutWhat
    {
        if (! array_key_exists(WireField::Service->value, $row)) {
            return AboutWhat::theMachine();
        }

        return AboutWhat::theService(self::saidIn($row, WireField::Service, $position));
    }

    /**
     * Which other check the row says explains it, where it says.
     *
     * The identifier is taken as a {@see Check} without asking whether the
     * report contains it. A report naming a check it does not hold is the
     * engine's fault and belongs on a screen as the identifier, where an
     * operator can quote it — refusing the whole report over it would turn one
     * wrong attribution into nine findings nobody can see.
     *
     * @param array<mixed> $row
     */
    private static function because(array $row, int $position): Because
    {
        if (! array_key_exists(DoctorField::CausedBy->value, $row)) {
            return Because::nothingElse();
        }

        return Because::theCheck(Check::of(self::saidIn($row, DoctorField::CausedBy, $position)));
    }

    /**
     * Who put the row's check there, read by the reader every attributing
     * envelope shares and refused with the report where it cannot be read.
     *
     * @param array<mixed> $row
     */
    private static function origin(array $row, int $position): WhoPutItThere
    {
        try {
            return Attributions::of($row);
        } catch (OriginIsUnreadable $why) {
            throw ReportIsUnreadable::origin($position, $why);
        }
    }

    private static function category(string $said): Category
    {
        return Category::tryFrom($said) ?? throw ReportIsUnreadable::category($said);
    }

    /**
     * What the core said about the check, off the same verdict.
     *
     * The verdict is a union on the wire: the passing arm carries an optional
     * note, and every other arm carries a `code`, a `meaning` and a list of
     * `remedies`. All three are what a finding has to carry, and a
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
     * Takes the conclusion too, rather than working out which arm this is from
     * whether a `code` key happens to be present. It is a *tagged* union and
     * `outcome` is the tag; sniffing for a field instead read `unverified` and
     * `skipped` as passing checks, because neither carries a code either. The
     * match is over the enum so that an outcome added later is a type error
     * here rather than a row that quietly lands on the passing arm.
     *
     * @param array<mixed> $verdict
     */
    private static function said(array $verdict, Conclusion $conclusion, int $position): WhatTheCheckSaid
    {
        return match ($conclusion) {
            Conclusion::Passed => WhatTheCheckSaid::nothingWrong(),
            Conclusion::Warned, Conclusion::Failed => WhatTheCheckSaid::wentWrong(
                Code::of(self::saidIn($verdict, WireField::Code, $position)),
                self::saidIn($verdict, WireField::Meaning, $position),
                self::remedies($verdict, $position),
                self::severity(self::saidIn($verdict, WireField::Severity, $position)),
                self::standing(self::saidIn($verdict, WireField::State, $position)),
                self::underneath($verdict),
            ),
            Conclusion::Unverified, Conclusion::Skipped => WhatTheCheckSaid::couldNotSay(
                self::saidIn($verdict, WireField::Reason, $position),
                self::remedy($verdict, $position),
            ),
        };
    }

    /**
     * The technical detail under a verdict, where the core gave one.
     *
     * Absent on most findings and optional on the wire, so its absence is the
     * ordinary case rather than a payload gone wrong — which is why this is the
     * one field here that does not refuse. Technical detail has to be available
     * where there is one, and says nothing about a core that has nothing to add.
     *
     * Blank is absent, for the reason {@see WhatItSaysUnderneath} gives: a core
     * that sent an empty string has said nothing, and a screen telling the two
     * apart would be drawing a distinction the operator cannot see.
     *
     * @param array<mixed> $verdict
     */
    private static function underneath(array $verdict): WhatItSaysUnderneath
    {
        // Written out rather than with `??`, which `C9` refuses on a subscript
        // and is right to: that idiom reads as a default and cannot be told
        // from one substituting for a field the payload should have carried.
        // This is the field where the distinction is real — the contract marks
        // it optional, so absent is the core having nothing to add rather than
        // a conversation gone wrong.
        if (! array_key_exists(WireField::Detail->value, $verdict)) {
            return WhatItSaysUnderneath::none();
        }

        $said = $verdict[WireField::Detail->value];

        return is_string($said) ? WhatItSaysUnderneath::said($said) : WhatItSaysUnderneath::none();
    }

    /**
     * The single remedy an `unverified` verdict offers, as a list of one.
     *
     * Singular on the wire and plural here, because a screen showing what to do
     * about a row should not care which outcome produced the row. `skipped`
     * carries none, which is the honest answer rather than an omission: a
     * prerequisite that was absent is a fact about the machine, not something
     * the core is asking anybody to go and fix.
     *
     * @param array<mixed> $verdict
     */
    private static function remedy(array $verdict, int $position): Remedies
    {
        if (! array_key_exists(WireField::Remedy->value, $verdict)) {
            return Remedies::none();
        }

        $offered = $verdict[WireField::Remedy->value];

        if (! is_array($offered)) {
            throw ReportIsUnreadable::inFinding($position, WireField::Remedy);
        }

        return Remedies::of(Remedy::of(self::saidIn($offered, WireField::Action, $position)));
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
    private static function remedies(array $verdict, int $position): Remedies
    {
        if (! array_key_exists(WireField::Remedies->value, $verdict)) {
            return Remedies::none();
        }

        $offered = $verdict[WireField::Remedies->value];

        if (! is_array($offered)) {
            throw ReportIsUnreadable::inFinding($position, WireField::Remedies);
        }

        $read = [];

        foreach ($offered as $one) {
            if (! is_array($one)) {
                throw ReportIsUnreadable::inFinding($position, WireField::Remedies);
            }

            $read[] = Remedy::of(self::saidIn($one, WireField::Action, $position));
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
    private static function verdict(array $row, int $position): array
    {
        if (! array_key_exists(DoctorField::Verdict->value, $row)) {
            throw ReportIsUnreadable::inFinding($position, DoctorField::Verdict);
        }

        $verdict = $row[DoctorField::Verdict->value];

        if (! is_array($verdict)) {
            throw ReportIsUnreadable::inFinding($position, DoctorField::Verdict);
        }

        return $verdict;
    }

    /**
     * The tag off the verdict, which is all this one reads.
     *
     * What the verdict also carries — the code, the meaning, the remedies — is
     * read by {@see self::said()}, which is where a finding is assembled.
     *
     * @param array<mixed> $verdict
     */
    private static function conclusion(array $verdict, int $position): Conclusion
    {
        $said = self::saidIn($verdict, WireField::Outcome, $position);

        return Conclusion::tryFrom($said) ?? throw ReportIsUnreadable::outcome($said);
    }
}
