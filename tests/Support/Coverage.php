<?php

declare(strict_types=1);

namespace Tests\Support;

use function file_get_contents;
use function is_file;
use function is_string;

use RuntimeException;

use function simplexml_load_string;
use function sprintf;
use function str_replace;
use function str_starts_with;

/**
 * A clover report, read per module.
 *
 * The report is the only place the numbers exist. Pest's `--min` compares one
 * percentage for the whole run, which is the thing this replaces: with twelve
 * modules behind a single number, a well-covered module carries a bare one and
 * the report says everything is fine. Splitting the same report by directory
 * costs nothing at the point of measurement and turns one number into twelve.
 *
 * `coveredstatements / statements` is the metric, because that is the one Pest
 * prints per file — a file it calls 50% has two of four covered statements, so
 * a floor written here means what the terminal already showed somebody.
 */
final readonly class Coverage
{
    /**
     * @param array<string, array{statements: int, covered: int}> $files
     */
    private function __construct(private array $files) {}

    /** Where `composer test:report` leaves the report. */
    public static function reportPath(): string
    {
        return Tree::at('coverage/clover.xml');
    }

    /** Whether a report is there to be read. */
    public static function reportExists(): bool
    {
        return is_file(self::reportPath());
    }

    public static function fromReport(): self
    {
        $xml = file_get_contents(self::reportPath());

        if (! is_string($xml)) {
            throw new RuntimeException(sprintf(
                'No coverage report at %s. Run `composer test:report`, which is what '
                . 'writes it — this suite reads the report rather than producing it, so '
                . 'that the numbers a gate checks are the same ones the run printed.',
                self::reportPath(),
            ));
        }

        return self::fromXml($xml);
    }

    public static function fromXml(string $xml): self
    {
        $report = simplexml_load_string($xml);

        if ($report === false) {
            throw new RuntimeException('The coverage report is not readable XML.');
        }

        $files = [];

        foreach ($report->xpath('//file') ?? [] as $file) {
            $metrics = $file->metrics;

            if ($metrics === null) {
                continue;
            }

            $files[self::relative((string) $file['name'])] = [
                'statements' => (int) $metrics['statements'],
                'covered' => (int) $metrics['coveredstatements'],
            ];
        }

        return new self($files);
    }

    /**
     * The covered and total statements under a repository-relative directory.
     *
     * @return array{statements: int, covered: int}
     */
    public function under(string $directory): array
    {
        $statements = 0;
        $covered = 0;

        foreach ($this->files as $path => $counts) {
            if (! str_starts_with($path, $directory)) {
                continue;
            }

            $statements += $counts['statements'];
            $covered += $counts['covered'];
        }

        return ['statements' => $statements, 'covered' => $covered];
    }

    /**
     * The percentage covered under a repository-relative directory, or null
     * where there is no code.
     *
     * Null rather than zero, and the difference matters: a module with nothing
     * in it is not a module that failed. Reporting it as 0% would fail every
     * empty module against any floor above zero, and the cure would be to lower
     * the floors — which is the opposite of the point.
     */
    public function percentageUnder(string $directory): ?float
    {
        $counts = $this->under($directory);

        if ($counts['statements'] === 0) {
            return null;
        }

        return $counts['covered'] / $counts['statements'] * 100;
    }

    /**
     * A path as the repository sees it.
     *
     * Clover writes absolute paths, which are the machine's rather than the
     * repository's — a report written in CI names a directory no laptop has.
     * Stripping the root keeps the comparison portable, and is what lets a
     * fixture report be written by hand.
     */
    private static function relative(string $path): string
    {
        return str_replace(sprintf('%s/', Tree::root()), '', $path);
    }
}
