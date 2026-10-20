<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** Where this phone could keep the key that proves its operator, or why it could not. */
final readonly class WhereThisPhoneCanKeepAKey
{
    private function __construct(private WhereAPhoneKeyLives|WhyThisPhoneHoldsNoKey $answer) {}

    /** It could, there. */
    public static function in(WhereAPhoneKeyLives $where): self
    {
        return new self($where);
    }

    /** It could not, for this reason. */
    public static function nowhere(WhyThisPhoneHoldsNoKey $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TIn of object
     * @template TNowhere of object
     *
     * @param Closure(WhereAPhoneKeyLives): TIn       $in
     * @param Closure(WhyThisPhoneHoldsNoKey): TNowhere $nowhere
     *
     * @return TIn|TNowhere
     */
    public function either(Closure $in, Closure $nowhere): object
    {
        return $this->answer instanceof WhereAPhoneKeyLives ? $in($this->answer) : $nowhere($this->answer);
    }
}
