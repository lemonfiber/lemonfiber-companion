<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The operator's yes to removing everything lemonfiber keeps, given against the listing that showed what would go.
 */
final readonly class ForgettingAgreed
{
    private function __construct(private TheForgetting $shown) {}

    /** The yes to this listing; a listing that is not awaiting a yes is refused. */
    public static function after(TheForgetting $shown): self
    {
        if (! $shown->got()->awaitsTheYes()) {
            throw ForgettingWasNotShown::first();
        }

        return new self($shown);
    }

    /** The listing the yes was given against. */
    public function shown(): TheForgetting
    {
        return $this->shown;
    }
}
