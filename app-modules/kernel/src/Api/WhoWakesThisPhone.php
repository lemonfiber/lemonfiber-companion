<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function count;

/**
 * Which distributor wakes this phone: the one installed, the one the operator chose among several, or why there is none.
 *
 * A choice of a distributor no longer installed is no choice, and is asked again where several are.
 */
final readonly class WhoWakesThisPhone
{
    private function __construct(private ?TheDistributors $installed, private ?ADistributor $through) {}

    /** Who wakes this phone, among what is installed, where the operator chose the package `$chosen` or nothing. */
    public static function among(WhatThisPhoneWakesThrough $installed, string $chosen): self
    {
        return $installed->either(
            installed: static fn(TheDistributors $distributors): self => new self($distributors, self::theOne($distributors, $chosen)),
            notHere: static fn(): self => new self(null, null),
        );
    }

    /**
     * @template TThrough of object
     * @template TToChoose of object
     * @template TNoneInstalled of object
     * @template TNotHere of object
     *
     * @param  Closure(ADistributor, TheDistributors): TThrough $through      one wakes it, among those installed
     * @param  Closure(TheDistributors): TToChoose              $toChoose     several are installed and the operator has not chosen one
     * @param  Closure(): TNoneInstalled                        $noneInstalled
     * @param  Closure(): TNotHere                              $notHere
     * @return TThrough|TToChoose|TNoneInstalled|TNotHere
     */
    public function either(Closure $through, Closure $toChoose, Closure $noneInstalled, Closure $notHere): object
    {
        if (! $this->installed instanceof TheDistributors) {
            return $notHere();
        }

        if ($this->through instanceof ADistributor) {
            return $through($this->through, $this->installed);
        }

        return count($this->installed) === 0 ? $noneInstalled() : $toChoose($this->installed);
    }

    private static function theOne(TheDistributors $installed, string $chosen): ?ADistributor
    {
        foreach ($installed as $distributor) {
            if (count($installed) === 1 || $distributor->package() === $chosen) {
                return $distributor;
            }
        }

        return null;
    }
}
