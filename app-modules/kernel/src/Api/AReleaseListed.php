<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One release in a stack's record, by its version, with what it set out to deliver. */
final readonly class AReleaseListed
{
    private function __construct(private string $version, private WhatAReleaseDelivers $delivers) {}

    /**
     * A release by its version.
     *
     * A blank version is refused, because a version is what names a release and
     * a release named by nothing could never be seen.
     */
    public static function versioned(string $version, WhatAReleaseDelivers $delivers): self
    {
        if (trim($version) === '') {
            throw VersionIsBlank::inARelease();
        }

        return new self($version, $delivers);
    }

    /** The version, without the tag's leading letter. */
    public function version(): string
    {
        return $this->version;
    }

    /** What it set out to deliver, where the record says. */
    public function delivers(): WhatAReleaseDelivers
    {
        return $this->delivers;
    }
}
