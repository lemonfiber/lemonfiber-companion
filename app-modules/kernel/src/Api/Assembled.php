<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A diagnostic report, assembled and waiting for the operator to send it.
 *
 * `N4-R13` has two clauses and the second is the whole design: a report is
 * assembled *for the operator to send*, and must not be transmitted by the app.
 * So this is a value with no way to send itself — no client, no port, no
 * `send()`. What it has is text and a name, which is what the platform's share
 * sheet needs, and the share sheet is the operator's own hands.
 *
 * That is not a technicality. A crash reporter is also "assembled and sent", and
 * the difference between it and this is entirely who pressed the button —
 * `N4-R12` refuses the first, and a type that could send itself would put the
 * two one line apart.
 */
final readonly class Assembled
{
    private function __construct(
        private string $named,
        private string $text,
    ) {}

    /**
     * The report, named and written.
     *
     * Internal to the kernel by convention rather than by keyword: PHP has no
     * package visibility, so what stops anybody assembling one is that
     * {@see Diagnostics} is the only thing that knows what belongs in it.
     */
    public static function as(string $named, string $text): self
    {
        return new self($named, $text);
    }

    /**
     * What the file is called when the operator shares it.
     *
     * A name rather than a path: where it is written is the adapter's business,
     * and a value that carried a path would be a value that knew about a
     * filesystem.
     */
    public function named(): string
    {
        return $this->named;
    }

    /** What the report says. */
    public function text(): string
    {
        return $this->text;
    }
}
