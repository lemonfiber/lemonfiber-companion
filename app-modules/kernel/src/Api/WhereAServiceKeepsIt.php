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
    private function __construct(private string $at, private string $unread, private string $none) {}

    /** The host path it is mounted from; a blank one is refused. */
    public static function at(string $path): self
    {
        return new self(self::said('at', $path), '', '');
    }

    /** It could not be read, and why; a blank reason is refused. */
    public static function unread(string $why): self
    {
        return new self('', self::said('unread', $why), '');
    }

    /** The service keeps none, and why; a blank reason is refused. */
    public static function none(string $why): self
    {
        return new self('', '', self::said('none', $why));
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
        return match (true) {
            $this->at !== '' => $at($this->at),
            $this->unread !== '' => $unread($this->unread),
            default => $none($this->none),
        };
    }

    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw TheSurveySaysNothing::about($field);
        }

        return $word;
    }
}
