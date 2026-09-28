<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One line of a file's diff, flattened for a template: whose it is, and what it says. */
final readonly class ADiffLineAsShown
{
    /**
     * @param string $said the catalogue key that marks it the operator's or lemonfiber's
     * @param string $line the line, as the stack sent it
     */
    public function __construct(
        public string $said,
        public string $line,
    ) {}
}
