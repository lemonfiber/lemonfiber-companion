<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One line of the difference between the operator's file and lemonfiber's.
 *
 * Theirs is the line a reset takes away; lemonfiber's is the line it writes in
 * its place. The text is as the stack sent it, a credential in it already
 * withheld by the stack.
 */
final readonly class ALineOfADiff
{
    private function __construct(private string $text, private bool $theirs) {}

    /** A line of the operator's, which putting the configuration back takes away. */
    public static function theirs(string $text): self
    {
        return new self($text, theirs: true);
    }

    /** A line of lemonfiber's, which putting the configuration back writes. */
    public static function lemonfibers(string $text): self
    {
        return new self($text, theirs: false);
    }

    /** Whether the line is the operator's rather than lemonfiber's. */
    public function isTheirs(): bool
    {
        return $this->theirs;
    }

    /** The line, as the stack sent it, without its mark. */
    public function text(): string
    {
        return $this->text;
    }
}
