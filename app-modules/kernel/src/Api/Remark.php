<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One sentence the stack says about a reading, in its own words.
 *
 * A type rather than a string, so the one place a sentence enters is where it
 * is refused for being blank (`D2`) — and a blank is what reads as *nothing is
 * happening* on a screen that exists to say what is.
 */
final readonly class Remark
{
    private function __construct(private string $said) {}

    /** The stack's sentence, refused where it is blank. */
    public static function said(string $said, string $field): self
    {
        if (trim($said) === '') {
            throw LineSaysNothing::about($field);
        }

        return new self($said);
    }

    /** The sentence, as the stack wrote it. */
    public function words(): string
    {
        return $this->said;
    }
}
