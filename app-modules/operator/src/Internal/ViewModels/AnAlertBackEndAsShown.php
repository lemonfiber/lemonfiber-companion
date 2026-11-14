<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One back-end alerts go to, as the operator reads it on Where alerts go and on its own page. */
final readonly class AnAlertBackEndAsShown
{
    /**
     * @param string                 $id       what the core names it by
     * @param string                 $goes     the path to its own page
     * @param string                 $kindSaid the key for what it speaks
     * @param string                 $where    where it delivers, with no credential in it
     * @param AnAlertLineAsShown           $chosen   who chose it
     * @param AHostAsShown           $hosted   where it is hosted, if it is
     * @param AnAlertLineAsShown           $proof    how far it is proven
     * @param ADeliveryAsShown       $last     its newest delivery, or that there is none yet
     * @param list<ADeliveryAsShown> $failures every delivery failed since the last confirmed one, newest first
     * @param ARetryAsShown          $retry    the next attempt, if one waits
     */
    public function __construct(
        public string $id,
        public string $goes,
        public string $kindSaid,
        public string $where,
        public AnAlertLineAsShown $chosen,
        public AHostAsShown $hosted,
        public AnAlertLineAsShown $proof,
        public ADeliveryAsShown $last,
        public array $failures,
        public ARetryAsShown $retry,
    ) {}
}
