<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** Where ending a grant has got to, flattened for a template. */
final readonly class TheCredentialRevokeTurnedOutToBe
{
    /**
     * @param WhereTheWorkStands             $stands      where the work stands, and whether the stack could be asked at all
     * @param ARefusalAsShown|null          $refused   the stack's refusal, where it refused
     * @param ACredentialRevokeAsShown|null $revoke    what the stack read or did, where it finished
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?ARefusalAsShown $refused,
        public ?ACredentialRevokeAsShown $revoke,
    ) {}
}
