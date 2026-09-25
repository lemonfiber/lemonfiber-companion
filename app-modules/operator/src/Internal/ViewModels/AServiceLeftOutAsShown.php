<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One service a form left out, flattened to a row. */
final readonly class AServiceLeftOutAsShown
{
    /**
     * @param string       $name      the service, as the stack names it
     * @param string       $needsSaid the catalogue key for what it would have needed
     * @param list<string> $askedBy   the forms that asked for it
     */
    public function __construct(
        public string $name,
        public string $needsSaid,
        public array $askedBy,
    ) {}
}
