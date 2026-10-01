<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function count;

use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhereTheOperatorWas;
use Modules\Kernel\Api\WhichTab;

/** Where the operator was, held in memory; or a store that keeps nothing it is told. */
final class WhereTheOperatorWasInMemory implements WhereTheOperatorWas
{
    private string $last = '';

    /** @var array<string, WhichTab> */
    private array $tabs = [];

    /** How many times the place was noted, kept or not. */
    private int $noted = 0;

    private function __construct(private readonly bool $keeps) {}

    public static function nowhere(): self
    {
        return new self(keeps: true);
    }

    public static function refusing(): self
    {
        return new self(keeps: false);
    }

    public function wasOn(StackId $stack, WhichTab $tab): bool
    {
        $this->noted++;

        if (! $this->keeps) {
            return false;
        }

        $this->last = $stack->stored();
        $this->tabs[$stack->stored()] = $tab;

        return true;
    }

    public function wasLastOn(StackId $stack): bool
    {
        return $this->last === $stack->stored();
    }

    public function tabOf(StackId $stack): WhichTab
    {
        return array_key_exists($stack->stored(), $this->tabs) ? $this->tabs[$stack->stored()] : WhichTab::Health;
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        if (! $this->keepsAnythingOf($stack)) {
            return Forgotten::nothing();
        }

        unset($this->tabs[$stack->stored()]);
        $this->last = $this->last === $stack->stored() ? '' : $this->last;

        return Forgotten::rows(1);
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->tabs) || $this->last === $stack->stored();
    }

    public function forgetEverything(): Forgotten
    {
        $held = count($this->tabs);
        $this->tabs = [];
        $this->last = '';

        return Forgotten::rows($held);
    }

    /** How many times the place was noted, kept or not. */
    public function timesNoted(): int
    {
        return $this->noted;
    }
}
