<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One part of a change to the line, as the operator wrote it, for the core to read.
 */
final readonly class APartWritten
{
    private function __construct(private APartOfTheLine $part, private string $text) {}

    /** The part and what was written for it, refused where nothing was. */
    public static function of(APartOfTheLine $part, string $text): self
    {
        $written = trim($text);

        if ($written === '') {
            throw LineChangeAsksNothing::ofThePart($part);
        }

        return new self($part, $written);
    }

    /** Which part this is. */
    public function part(): APartOfTheLine
    {
        return $this->part;
    }

    /** What was written for it. */
    public function text(): string
    {
        return $this->text;
    }
}
