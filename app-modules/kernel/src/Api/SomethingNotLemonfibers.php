<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Something beneath the data location that the stack did not put there, with how much and how many.
 *
 * Named apart from what lemonfiber reaches, because an operator deciding
 * whether a directory goes needs to know some of what is in it is theirs.
 */
final readonly class SomethingNotLemonfibers
{
    private function __construct(private string $at, private int $files, private int $bytes) {}

    /** What was found at that place; a blank place, or a count below none, is refused. */
    public static function at(string $at, int $files, int $bytes): self
    {
        if (trim($at) === '') {
            throw UninstallSaysNothing::about('foreign.at');
        }

        if ($files < 0) {
            throw UninstallSaysNothing::outside('foreign.files', $files);
        }

        if ($bytes < 0) {
            throw UninstallSaysNothing::outside('foreign.bytes', $bytes);
        }

        return new self($at, $files, $bytes);
    }

    /** Where it is, relative to the data location. */
    public function where(): string
    {
        return $this->at;
    }

    /** How many files were found under it. */
    public function files(): int
    {
        return $this->files;
    }

    /** What they occupy. */
    public function bytes(): int
    {
        return $this->bytes;
    }
}
