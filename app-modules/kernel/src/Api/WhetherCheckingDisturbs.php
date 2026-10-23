<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether a finding was left unverified because checking it disturbs something, and if so, the check that would.
 *
 * Two arms rather than a nullable check, so a screen says what it draws for a
 * finding with nothing to run rather than finding out.
 */
final readonly class WhetherCheckingDisturbs
{
    private function __construct(private ?ACheckThatDisturbs $check) {}

    /** It was left unverified, and this is the check that would verify it. */
    public static function so(ACheckThatDisturbs $check): self
    {
        return new self($check);
    }

    /** Nothing about it waits on a check that disturbs. */
    public static function not(): self
    {
        return new self(null);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TSo of object
     * @template TNot of object
     *
     * @param Closure(ACheckThatDisturbs): TSo $so
     * @param Closure(): TNot                  $not
     *
     * @return TSo|TNot
     */
    public function either(Closure $so, Closure $not): object
    {
        return $this->check instanceof ACheckThatDisturbs ? $so($this->check) : $not();
    }
}
