<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Where a service found already here keeps its configuration or its library on this machine, or why that is not said.
 *
 * Three answers, kept apart: the host path it is mounted from, a place that
 * could not be read, and a service that keeps none. Each of the last two
 * carries the stack's reason, in the operator's words.
 */
final readonly class WhereAServiceKeepsIt
{
    /** @param non-empty-string $said the host path, or the stack's reason */
    private function __construct(private WhatASurveySaysOfAPlace $answer, private string $said) {}

    /** The host path it is mounted from; a blank one is refused. */
    public static function at(string $path): self
    {
        return new self(WhatASurveySaysOfAPlace::MountedFrom, self::said('at', $path));
    }

    /** It could not be read, and why; a blank reason is refused. */
    public static function unread(string $why): self
    {
        return new self(WhatASurveySaysOfAPlace::Unread, self::said('unread', $why));
    }

    /** The service keeps none, and why; a blank reason is refused. */
    public static function none(string $why): self
    {
        return new self(WhatASurveySaysOfAPlace::KeepsNone, self::said('none', $why));
    }

    /**
     * @template TAt of object
     * @template TUnread of object
     * @template TNone of object
     *
     * @param  Closure(string): TAt     $at
     * @param  Closure(string): TUnread $unread
     * @param  Closure(string): TNone   $none
     * @return TAt|TUnread|TNone
     */
    public function either(Closure $at, Closure $unread, Closure $none): object
    {
        return match ($this->answer) {
            WhatASurveySaysOfAPlace::MountedFrom => $at($this->said),
            WhatASurveySaysOfAPlace::Unread => $unread($this->said),
            WhatASurveySaysOfAPlace::KeepsNone => $none($this->said),
        };
    }

    /** @return non-empty-string */
    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw TheSurveySaysNothing::about($field);
        }

        return $word;
    }
}
