<?php

declare(strict_types=1);

namespace Modules\Vault\Api;

use function count;

use Lemonfiber\Native\Keeps;
use Lemonfiber\Native\WhenAValueMayBeRead;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhereTheOperatorWas;
use Modules\Kernel\Api\WhichTab;
use Modules\Vault\Internal\KeptUnder;
use Modules\Vault\Internal\ThePlaceAsWritten;
use Modules\Vault\Internal\WhetherAnythingIsHeld;

/**
 * Where the operator was, kept in the platform's own store as one record.
 *
 * A record this build did not write reads as nowhere: the app then opens on
 * the first stack in the operator's order, on Health, which is where a first
 * opening goes anyway.
 */
final readonly class PlatformWhereTheOperatorWas implements WhereTheOperatorWas
{
    public function __construct(private Keeps $store) {}

    /** Written only where the place changed: a screen is built far more often than the operator moves. */
    public function wasOn(StackId $stack, WhichTab $tab): bool
    {
        $place = $this->place();
        $now = $place->on($stack, $tab);

        return $now === $place || $this->kept($now);
    }

    public function wasLastOn(StackId $stack): bool
    {
        return $this->place()->last === $stack->stored();
    }

    public function tabOf(StackId $stack): WhichTab
    {
        return $this->place()->tabOf($stack);
    }

    public function forgetTheStack(StackId $stack): Forgotten
    {
        $place = $this->place();

        if (! $place->names($stack)) {
            return Forgotten::nothing();
        }

        return $this->kept($place->without($stack)) ? Forgotten::rows(1) : Forgotten::nothing();
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->store->read(KeptUnder::WhereTheOperatorWas->value)->either(
            found: static fn(string $written): WhetherAnythingIsHeld => ThePlaceAsWritten::read($written)->names($stack)
                ? WhetherAnythingIsHeld::itIs()
                : WhetherAnythingIsHeld::itIsNot(),
            nothing: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
        )->held;
    }

    public function forgetEverything(): Forgotten
    {
        $held = count($this->place()->tabs);

        return $this->store->forget(KeptUnder::WhereTheOperatorWas->value)->either(
            done: static fn(): Forgotten => Forgotten::rows($held),
            refused: static fn(): Forgotten => Forgotten::nothing(),
        );
    }

    /** The record as the store holds it; one that cannot be read is nowhere. */
    private function place(): ThePlaceAsWritten
    {
        return $this->store->read(KeptUnder::WhereTheOperatorWas->value)->either(
            found: static fn(string $written): ThePlaceAsWritten => ThePlaceAsWritten::read($written),
            nothing: static fn(): ThePlaceAsWritten => ThePlaceAsWritten::nowhere(),
            refused: static fn(): ThePlaceAsWritten => ThePlaceAsWritten::nowhere(),
        );
    }

    /** Write the record down in place of the one before, and say whether it was. */
    private function kept(ThePlaceAsWritten $place): bool
    {
        $written = $place->written();

        if ($written === false) {
            return false;
        }

        return $this->store->keep(KeptUnder::WhereTheOperatorWas->value, $written, WhenAValueMayBeRead::WhileUnlocked)->either(
            done: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIs(),
            refused: static fn(): WhetherAnythingIsHeld => WhetherAnythingIsHeld::itIsNot(),
        )->held;
    }
}
