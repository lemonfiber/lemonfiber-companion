<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** Where alerts go, as the machine answered, or what the operator met instead. */
final readonly class WhereAlertsGoCameBack
{
    private function __construct(private WhereAlertsGo|Obstacle $answer) {}

    public static function read(WhereAlertsGo $read): self
    {
        return new self($read);
    }

    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * @template TRead of object
     * @template TMet of object
     *
     * @param  Closure(WhereAlertsGo): TRead $read
     * @param  Closure(Obstacle): TMet       $met
     * @return TRead|TMet
     */
    public function either(Closure $read, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $read($this->answer);
    }
}
