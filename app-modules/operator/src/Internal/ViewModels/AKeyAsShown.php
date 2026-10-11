<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Operator\Internal\Presenters\AgoAsShown;

/** One integration key, as the card that draws it. */
final readonly class AKeyAsShown
{
    /**
     * @param string             $name         the name it was minted under, which revoking it names
     * @param string             $admits       the catalogue key for what it admits
     * @param array<string, string> $admitsFilling the member a member's key admits, or nothing
     * @param string             $forSaid      the catalogue key for what its minter said it is for
     * @param string             $standsSaid   the catalogue key for where it stands
     * @param AgoAsShown         $minted       how long ago it was minted
     * @param AgoAsShown         $used         how long ago it was last admitted, unsaid where never
     * @param bool               $byAMember    whether a household member minted it for themselves
     * @param bool               $mayBeRevoked whether revoking it is offered
     */
    public function __construct(
        public string $name,
        public string $admits,
        public array $admitsFilling,
        public string $forSaid,
        public string $standsSaid,
        public AgoAsShown $minted,
        public AgoAsShown $used,
        public bool $byAMember,
        public bool $mayBeRevoked,
    ) {}
}
