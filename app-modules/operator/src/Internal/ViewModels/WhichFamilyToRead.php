<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One family of checks, as a control a template can draw.
 *
 * Four things — stuck downloads, provider health, disk
 * pressure and VPN verification — each be reachable. Each is a
 * {@see \Modules\Kernel\Api\Category} the engine already sorts its findings
 * into, so what the requirement needs is a way to reach one without reading
 * past the others.
 *
 * `Internal` because it is a detail of how one surface offers a narrowing, and
 * `E2`'s promise is that anything here can be renamed without reading another
 * module.
 */
final readonly class WhichFamilyToRead
{
    /**
     * @param string $said     the key for what this family is called
     * @param string $family   what the screen stores while reading it
     * @param int    $howMany  how many findings it holds, always one or more
     * @param bool   $isOpen   whether this is the family being read
     */
    public function __construct(
        public string $said,
        public string $family,
        public int $howMany,
        public bool $isOpen,
    ) {}
}
