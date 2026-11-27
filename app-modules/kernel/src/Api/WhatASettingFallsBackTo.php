<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a setting falls back to while it holds no operator value, as the stack said it when the setting moved.
 *
 * Only the readers whose needs are met are held: an unmet reader writes nothing, so its default is nothing the setting falls back to.
 */
final readonly class WhatASettingFallsBackTo
{
    private function __construct(private WhereADefaultComesFrom $from, private ASettingValue $value, private TheServicesReadingIt $readers) {}

    /** One of the core's own settings, with what the core reads while none is recorded. */
    public static function theCores(string $value): self
    {
        return new self(WhereADefaultComesFrom::TheCore, ASettingValue::value($value), TheServicesReadingIt::these());
    }

    /** The one default every reader whose needs are met declares; empty is empty by default. */
    public static function declared(string $value): self
    {
        return new self(WhereADefaultComesFrom::Declared, ASettingValue::value($value), TheServicesReadingIt::these());
    }

    /** Each reader's own, where the readers whose needs are met declare different ones. */
    public static function eachReadersOwn(TheServicesReadingIt $met): self
    {
        return new self(WhereADefaultComesFrom::EachReader, ASettingValue::nothing(), $met);
    }

    /** Nothing declares one. */
    public static function nothing(): self
    {
        return new self(WhereADefaultComesFrom::Nowhere, ASettingValue::nothing(), TheServicesReadingIt::these());
    }

    /** Where it comes from. */
    public function from(): WhereADefaultComesFrom
    {
        return $this->from;
    }

    /** The one value it falls back to, or none where each reader has its own or nothing declares one. */
    public function value(): ASettingValue
    {
        return $this->value;
    }

    /** Each reader whose needs are met, with its own default, where the readers differ. */
    public function readers(): TheServicesReadingIt
    {
        return $this->readers;
    }

    /** Whether a value is the one it falls back to. */
    public function isTheSameAs(ASettingValue $value): bool
    {
        return $value->isHeld() && $this->value->isHeld() && $value->said() === $this->value->said();
    }
}
