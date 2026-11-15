<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** A UnifiedPush distributor installed on this phone: the package Android knows it by, and what it is called. */
final readonly class ADistributor
{
    private function __construct(private string $package, private string $called) {}

    /** The distributor installed as `$package`, called `$called`; a blank package is refused, and a blank name is the package. */
    public static function of(string $package, string $called): self
    {
        if (trim($package) === '') {
            throw AWakeIsUnfit::aPackage();
        }

        return new self($package, trim($called) === '' ? $package : $called);
    }

    /** The package Android knows it by. */
    public function package(): string
    {
        return $this->package;
    }

    /** What it is called on the screen. */
    public function called(): string
    {
        return $this->called;
    }

    /** Whether this is the distributor installed as the same package. */
    public function is(self $other): bool
    {
        return $this->package === $other->package;
    }
}
