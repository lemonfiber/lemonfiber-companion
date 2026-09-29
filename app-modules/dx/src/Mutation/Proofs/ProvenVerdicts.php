<?php

declare(strict_types=1);

namespace Dx\Mutation\Proofs;

use function array_key_exists;
use function array_slice;
use function count;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use JsonException;

use function preg_match;
use function uasort;

/**
 * Which mutation verdicts have held, by the digest of what each one read.
 *
 * A proof names the run that established it, so a unit that is not mutated
 * again says which run it rests on. Anything in a ledger that is not exactly a
 * proof — a file that does not parse, an entry with a missing or malformed
 * field — is dropped rather than repaired, and a unit whose proof was dropped
 * is mutated: an unreadable ledger costs a run and never a verdict.
 */
final readonly class ProvenVerdicts
{
    /** How many proofs a ledger keeps, newest first; older ones are for code long since changed. */
    public const int KEPT = 20_000;

    /** @param array<string, array{run: int, attempt: int, at: string, unit: string}> $proofs */
    private function __construct(private array $proofs) {}

    public static function none(): self
    {
        return new self([]);
    }

    /** The proofs a ledger holds, keeping each well-formed entry and nothing else. */
    public static function read(string $ledger): self
    {
        try {
            $decoded = json_decode($ledger, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return self::none();
        }

        $entries = is_array($decoded) && array_key_exists('proofs', $decoded) && is_array($decoded['proofs']) ? $decoded['proofs'] : [];
        $proofs = [];

        foreach ($entries as $key => $entry) {
            $proof = self::proofIn($key, $entry);

            if ($proof !== null) {
                $proofs[(string) $key] = $proof;
            }
        }

        return new self($proofs);
    }

    /**
     * The run a digest was proved in, or null where it was not.
     *
     * @return array{run: int, attempt: int, at: string, unit: string}|null
     */
    public function of(string $digest): ?array
    {
        return array_key_exists($digest, $this->proofs) ? $this->proofs[$digest] : null;
    }

    /** These proofs and a new one; a digest proved twice keeps its first proof. */
    public function with(string $digest, int $run, int $attempt, string $at, string $unit): self
    {
        return new self([...[$digest => ['run' => $run, 'attempt' => $attempt, 'at' => $at, 'unit' => $unit]], ...$this->proofs]);
    }

    /** Both ledgers' proofs; where both prove a digest, this one's is kept. */
    public function and(self $other): self
    {
        return new self([...$other->proofs, ...$this->proofs]);
    }

    /** The ledger as a file, holding the newest {@see KEPT} proofs. */
    public function written(): string
    {
        $proofs = $this->proofs;
        uasort($proofs, static fn(array $one, array $other): int => [$other['at'], $other['run']] <=> [$one['at'], $one['run']]);

        return json_encode(['proofs' => array_slice($proofs, 0, self::KEPT, preserve_keys: true)], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function count(): int
    {
        return count($this->proofs);
    }

    /** @return array{run: int, attempt: int, at: string, unit: string}|null */
    private static function proofIn(int|string $key, mixed $entry): ?array
    {
        $wellFormed = is_string($key)
            && preg_match('/^[0-9a-f]{64}$/u', $key) === 1
            && is_array($entry)
            && self::positive($entry, 'run')
            && self::positive($entry, 'attempt')
            && self::text($entry, 'at')
            && self::text($entry, 'unit');

        if (! $wellFormed) {
            return null;
        }

        /** @var array{run: int, attempt: int, at: string, unit: string} $entry */
        return ['run' => $entry['run'], 'attempt' => $entry['attempt'], 'at' => $entry['at'], 'unit' => $entry['unit']];
    }

    /** @param array<mixed> $entry */
    private static function positive(array $entry, string $field): bool
    {
        return array_key_exists($field, $entry) && is_int($entry[$field]) && $entry[$field] > 0;
    }

    /** @param array<mixed> $entry */
    private static function text(array $entry, string $field): bool
    {
        return array_key_exists($field, $entry) && is_string($entry[$field]) && $entry[$field] !== '';
    }
}
