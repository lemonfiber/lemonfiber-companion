<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What this app may ask a stack to do about the plugins that extend it.
 *
 * A closed set for {@see AskingThemIn}'s reason: an action's name is the last
 * segment of the path it is asked for, and a name spelled at the call site is
 * this app able to ask for any action a stack offers.
 */
enum ExtendingIt: string implements AnAction
{
    /** Install a plugin, rehearsed first and agreed to against the rehearsal. */
    case Install = 'install';

    /** Move an installed plugin to the version its source serves now, rehearsed first. */
    case Update = 'update';

    /** Take an installed plugin off the machine, rehearsed first. */
    case Remove = 'remove';

    /**
     * lemonfiber's word for it.
     *
     * Apart from the case's own value for the reason {@see WhatWasDecided}
     * gives: this app's word for a thing is not the wire's.
     */
    public function asked(): string
    {
        return match ($this) {
            self::Install => 'plugin-install',
            self::Update => 'plugin-update',
            self::Remove => 'plugin-remove',
        };
    }
}
