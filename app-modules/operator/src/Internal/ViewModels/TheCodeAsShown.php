<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A sign-in code as the screen draws it: the code, when it lapses, and the page to approve it at as squares to scan.
 */
final readonly class TheCodeAsShown
{
    /**
     * @param string           $code     the code as the house answered it
     * @param string           $lapsesAt when it lapses, on the phone's clock
     * @param list<list<bool>> $squares  the page to approve it at, as a code another device scans
     */
    public function __construct(
        public string $code,
        public string $lapsesAt,
        public array $squares,
    ) {}
}
