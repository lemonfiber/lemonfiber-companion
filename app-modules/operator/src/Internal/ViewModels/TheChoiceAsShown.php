<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A choice of what fills a capability, as a template draws it before the yes.
 *
 * Every name is the stack's. What answers the capability now and what would
 * answer it after are two lines, so the change is read as one; what asks for
 * it and what the choice would leave unfilled follow, all before anything is
 * agreed to.
 */
final readonly class TheChoiceAsShown
{
    /**
     * @param array<string, string>              $reachesWith what fills the line that says what answers it now
     * @param list<array{by: string, capability: string}> $leaves every service that would lose a capability, with the capability
     */
    public function __construct(
        public string $capability,
        public string $now,
        public string $reachesSaid,
        public array $reachesWith,
        public string $askedBy,
        public array $leaves,
    ) {}
}
