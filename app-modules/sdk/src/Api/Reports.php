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
use Modules\Kernel\Api\Conclusion;
use Modules\Kernel\Api\Finding;
use Modules\Kernel\Api\Findings;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Report;
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
            throw ReportIsUnreadable::missing('data');
        }

        return Report::of(self::overall(self::text($data, 'overall')), self::findings($data));
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
    private static function text(array $data, string $field): string
    {
        if (! array_key_exists($field, $data)) {
            throw ReportIsUnreadable::missing($field);
        }

        $said = $data[$field];

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
        if (! array_key_exists('findings', $data)) {
            throw ReportIsUnreadable::missing('findings');
        }

        $rows = $data['findings'];

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
            Check::of(self::text($row, 'check')),
            self::category(self::text($row, 'category')),
            self::text($row, 'title'),
            self::conclusion($row),
        );
    }

    private static function category(string $said): Category
    {
        return Category::tryFrom($said) ?? throw ReportIsUnreadable::category($said);
    }

    /**
     * The tag off the verdict, and nothing else it carries.
     *
     * @param array<mixed> $row
     */
    private static function conclusion(array $row): Conclusion
    {
        if (! array_key_exists('verdict', $row)) {
            throw ReportIsUnreadable::missing('verdict');
        }

        $verdict = $row['verdict'];

        if (! is_array($verdict)) {
            throw ReportIsUnreadable::missing('verdict');
        }

        $said = self::text($verdict, 'outcome');

        return Conclusion::tryFrom($said) ?? throw ReportIsUnreadable::outcome($said);
    }
}
