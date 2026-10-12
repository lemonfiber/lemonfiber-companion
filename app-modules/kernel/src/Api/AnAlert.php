<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One alert the core raised: what kind of event it is, which way it went, and the name that is the same on its onset and its resolution.
 *
 * It carries no sentence. What happened, what it means and what to do are
 * read in the app; a notification says only that the stack has something to
 * say, under the kind the core declared.
 */
final readonly class AnAlert
{
    private function __construct(private WhatTheCoreDecided $kind, private WhichWayItWent $way, private AnAlertsName $filedUnder) {}

    /** The alert, filed under the name the core gave it. */
    public static function raised(WhatTheCoreDecided $kind, WhichWayItWent $way, AnAlertsName $named): self
    {
        return new self($kind, $way, $named);
    }

    /** The alert, filed under its kind, as it is where the core recorded it before alerts carried a name. */
    public static function unnamed(WhatTheCoreDecided $kind, WhichWayItWent $way): self
    {
        return new self($kind, $way, AnAlertsName::byItsKind($kind));
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
    public function filedUnder(): AnAlertsName
    {
        return $this->filedUnder;
    }
}
