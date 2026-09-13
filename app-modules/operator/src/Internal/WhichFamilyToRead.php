<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Category;

/**
 * One family of checks, as a control a template can draw.
 *
 * `N2-R9` asks that four things — stuck downloads, provider health, disk
 * pressure and VPN verification — each be reachable. Each is a
 * {@see Category} the engine already sorts its findings into, so what the
 * requirement needs is a way to reach one without reading past the others.
 *
 * **The count is on the control, not behind it.** An operator deciding which
 * family to open is deciding where the trouble is, and a row of names with no
 * numbers makes them open each one to find out. It is also what makes the
 * control honest about itself: a family with nothing in it is never drawn.
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
    private function __construct(
        public string $said,
        public string $family,
        public int $howMany,
        public bool $isOpen,
    ) {}

    public static function of(Category $family, int $howMany, bool $isOpen): self
    {
        return new self($family->saidOnTheScreen(), $family->value, $howMany, $isOpen);
    }
}
