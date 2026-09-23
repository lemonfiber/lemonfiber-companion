<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where one service comes from, flattened for a template to read.
 *
 * Every field is always set, because the value it comes from refuses to be
 * built without it; this must not undo that by defaulting one. A licence in
 * particular is never empty here, so the template draws it on every row
 * rather than where it is unusual.
 */
final readonly class WhereOneServiceComesFrom
{
    /**
     * @param string $name     what it is called in front of an operator
     * @param string $image    the image it runs, without a version
     * @param string $pinned   the exact version this stack pins it at
     * @param string $upstream the project it is built from
     * @param string $licence  the licence it is published under
     */
    public function __construct(
        public string $name,
        public string $image,
        public string $pinned,
        public string $upstream,
        public string $licence,
    ) {}
}
