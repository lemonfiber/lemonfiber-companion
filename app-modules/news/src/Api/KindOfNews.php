<?php

declare(strict_types=1);

namespace Modules\News\Api;

/**
 * The three kinds of thing a stack can have that is new to the operator.
 *
 * Each is newer by an order the stack vouches for: an update by its place in
 * the stack's record, a request by its number, and a problem by when it began.
 * The value is what a kept row names the kind by.
 */
enum KindOfNews: string
{
    /** A release in the stack's record. */
    case Update = 'update';

    /** A request somebody in the household made. */
    case Request = 'request';

    /** A check the stack found wrong. */
    case Problem = 'problem';

    /** The key for what the kind is called on a screen, many of it. */
    public function saidOnTheScreen(): string
    {
        return match ($this) {
            self::Update => 'news.kind.update',
            self::Request => 'news.kind.request',
            self::Problem => 'news.kind.problem',
        };
    }
}
