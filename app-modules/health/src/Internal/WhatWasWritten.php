<?php

declare(strict_types=1);

namespace Modules\Health\Internal;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

/**
 * The fields of a kept summary, read one type at a time.
 *
 * Every read names the type it wants and refuses anything else with
 * {@see KeptSummaryDoesNotRead}, so the one place a kept summary is turned back
 * into values asks for each field once and never checks a type itself.
 */
final readonly class WhatWasWritten
{
    /** @param array<mixed> $fields */
    private function __construct(private array $fields) {}

    /** What a decoded value holds, where it holds fields at all. */
    public static function in(mixed $decoded): self
    {
        return is_array($decoded) ? new self($decoded) : throw KeptSummaryDoesNotRead::asFields();
    }

    public function text(string $field): string
    {
        $value = $this->field($field);

        return is_string($value) ? $value : throw KeptSummaryDoesNotRead::at($field);
    }

    public function number(string $field): int
    {
        $value = $this->field($field);

        return is_int($value) ? $value : throw KeptSummaryDoesNotRead::at($field);
    }

    /**
     * A list of lines, each a string.
     *
     * @return list<string>
     */
    public function lines(string $field): array
    {
        $lines = [];

        foreach ($this->listAt($field) as $line) {
            $lines[] = is_string($line) ? $line : throw KeptSummaryDoesNotRead::at($field);
        }

        return $lines;
    }

    /**
     * A list of entries, each a set of fields of its own.
     *
     * @return list<self>
     */
    public function entries(string $field): array
    {
        $entries = [];

        foreach ($this->listAt($field) as $entry) {
            $entries[] = self::in($entry);
        }

        return $entries;
    }

    /** @return array<mixed> */
    private function listAt(string $field): array
    {
        $value = $this->field($field);

        return is_array($value) ? $value : throw KeptSummaryDoesNotRead::at($field);
    }

    private function field(string $field): mixed
    {
        return array_key_exists($field, $this->fields) ? $this->fields[$field] : throw KeptSummaryDoesNotRead::at($field);
    }
}
