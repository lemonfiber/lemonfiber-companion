<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One of the newest releases a stack names, by its version and nothing else. */
final readonly class AReleaseNamed
{
    private function __construct(private string $version) {}

    /**
     * A release by its version.
     *
     * A blank version is refused, because a version is what names a release and
     * a release named by nothing could never be seen.
     */
    public static function versioned(string $version): self
    {
        if (trim($version) === '') {
            throw VersionIsBlank::inARelease();
        }

        return new self($version);
    }

    /** The version, as the stack wrote it. */
    public function version(): string
    {
        return $this->version;
    }
}
