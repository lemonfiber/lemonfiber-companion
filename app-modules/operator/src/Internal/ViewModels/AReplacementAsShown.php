<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What replacing the internal certificate would do, or what it came to, flattened for a template.
 */
final readonly class AReplacementAsShown
{
    /**
     * @param bool                 $reading    whether this is a reading a yes may agree to, nothing replaced yet
     * @param bool                 $landed     whether it was replaced and is the one in force
     * @param string               $settledSaid the catalogue key for where it settled
     * @param string               $said       what the stack said of it, in its own words, or empty
     * @param list<AHolderAsShown> $holders    each holder, and how far the replacement reached it
     * @param string               $inForce    the certificate in force after it, at a glance, or empty where the stack did not say
     * @param string               $tone       how it is drawn: a reading wants looking at, a landed one is fine, and anything else is not known to have landed
     */
    public function __construct(
        public bool $reading,
        public bool $landed,
        public string $settledSaid,
        public string $said,
        public array $holders,
        public string $inForce,
        public string $tone,
    ) {}
}
