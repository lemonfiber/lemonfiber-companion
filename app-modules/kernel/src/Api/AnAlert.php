<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One alert the core raised: what kind of event it is, which way it went, and the name that is the same on its onset and its resolution.
 *
 * It carries no sentence. What happened, what it means and what to do are
 * read in the app; a notification says only that the stack has something to
 * say, under the kind the core declared.
 */
final readonly class AnAlert
{
    private function __construct(private WhatTheCoreDecided $kind, private WhichWayItWent $way, private WhatTheCoreDecided $filedUnder) {}

    /** The alert, filed under its kind where its name is blank, as it is where the core recorded it before alerts carried one. */
    public static function raised(WhatTheCoreDecided $kind, WhichWayItWent $way, string $id): self
    {
        return new self($kind, $way, trim($id) === '' ? $kind : WhatTheCoreDecided::toSay($id));
    }

    /** What kind of event it is. */
    public function kind(): WhatTheCoreDecided
    {
        return $this->kind;
    }

    /** Which way it went. */
    public function way(): WhichWayItWent
    {
        return $this->way;
    }

    /** What a notification about it is filed under: its name, so a resolution replaces its onset, or its kind where it has no name. */
    public function filedUnder(): WhatTheCoreDecided
    {
        return $this->filedUnder;
    }
}
