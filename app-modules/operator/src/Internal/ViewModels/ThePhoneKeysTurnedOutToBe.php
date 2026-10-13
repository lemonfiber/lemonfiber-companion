<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What asking a stack for its phone keys, or revoking one, came to, as a screen draws it. */
final readonly class ThePhoneKeysTurnedOutToBe
{
    /**
     * @param list<APhoneKeyAsShown> $keys    every phone key, in the order they were registered
     * @param ARefusalAsShown|null   $refused the stack's refusal of a revoke, in its words, where it refused one
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $keys,
        public ?ARefusalAsShown $refused,
    ) {}
}
