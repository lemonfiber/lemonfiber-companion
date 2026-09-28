<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One file putting the configuration back reverts, flattened for a template. */
final readonly class AnEditAsShown
{
    /**
     * @param string             $path  the file's path within the stack directory
     * @param list<ADiffLineAsShown> $lines the lines that differ, the operator's and lemonfiber's, in the stack's order
     */
    public function __construct(
        public string $path,
        public array $lines,
    ) {}
}
