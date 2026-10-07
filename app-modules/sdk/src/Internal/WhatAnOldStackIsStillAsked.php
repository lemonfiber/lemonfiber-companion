<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\TakingAnUpdate;

/**
 * What is still asked of a stack too old to say what it can do, and why each.
 *
 * A stack that has no way to declare what it serves is older than the
 * declaration, and everything it might be asked is reported as needing a
 * newer lemonfiber rather than attempted. Except the way out: an operator
 * looking at a stack that is too old for everything has to be able to update
 * it from here, so what an update would bring and taking it are asked as
 * before.
 */
final readonly class WhatAnOldStackIsStillAsked
{
    /**
     * Each path still asked, and the reason it is.
     *
     * @return array<string, string>
     */
    public static function andWhy(): array
    {
        return [
            Api::UPDATE_ENDPOINT => 'what an update would bring is how the operator learns there is a newer lemonfiber to take',
            Api::action(TakingAnUpdate::named()) => 'taking the update is the way out of being too old to say what it can do',
        ];
    }

    /** Whether this path is still asked of a stack too old to say what it can do. */
    public static function at(Ability $path): bool
    {
        return array_key_exists($path->named(), self::andWhy());
    }
}
