<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** How many starts in a row the stack has failed to come back after, and since when, or that it is not failing. */
final readonly class HowLongItHasFailed
{
    private function __construct(private int $starts, private WhenItSays $since) {}

    /** It came back. */
    public static function notFailing(): self
    {
        return new self(0, WhenItSays::unsaid());
    }

    /** It has failed to come back after this many starts in a row, since the moment the stack names. */
    public static function after(int $starts, WhenItSays $since): self
    {
        if ($starts < 1) {
            throw ARestartSaysTooLittle::about('failed_boots');
        }

        return new self($starts, $since);
    }

    /**
     * Say what happens where it is failing and where it is not.
     *
     * @template TFailing of object
     * @template TNot of object
     *
     * @param  Closure(int, WhenItSays): TFailing $failing
     * @param  Closure(): TNot                    $notFailing
     * @return TFailing|TNot
     */
    public function either(Closure $failing, Closure $notFailing): object
    {
        return $this->starts > 0 ? $failing($this->starts, $this->since) : $notFailing();
    }
}
