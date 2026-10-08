<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One ask an install would leave contested: what is asked for, by what, and every service claiming it. */
final readonly class AContestAsShown
{
    /**
     * @param string       $capability what is asked for
     * @param string       $by         what asks for it, or empty
     * @param list<string> $claimants  every service that would claim it
     */
    public function __construct(public string $capability, public string $by, public array $claimants) {}
}
