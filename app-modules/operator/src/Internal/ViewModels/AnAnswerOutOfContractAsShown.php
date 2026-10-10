<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One answer a plugin's adapter gave outside its contract, as its card says it. */
final readonly class AnAnswerOutOfContractAsShown
{
    /**
     * @param string $capability the capability it was asked as
     * @param string $operation  the operation it was asked
     * @param string $why        what was outside the contract, in the stack's words
     */
    public function __construct(
        public string $capability,
        public string $operation,
        public string $why,
    ) {}
}
