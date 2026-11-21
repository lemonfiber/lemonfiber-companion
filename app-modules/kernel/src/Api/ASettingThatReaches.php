<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The setting the stack names as the one that turns reaching its catalogue on: its key, the value that turns it on, and the act that writes it (ARCH-R450).
 *
 * This app writes exactly what the stack names and hardcodes neither: a key
 * or a value spelled here would be this app deciding something the stack
 * owns. An act it does not know is refused, so nothing is offered that the
 * reviewed change cannot carry.
 */
final readonly class ASettingThatReaches
{
    private function __construct(private string $key, private string $value) {}

    /** The setting as the stack named it; a blank key or value, or an act other than the one this app reviews, is refused. */
    public static function named(string $key, string $value, string $action): self
    {
        if ($action !== WhatToChange::Setting->asked()) {
            throw ThePluginCatalogueIsUnreadable::writtenBy($action);
        }

        foreach (['setting.key' => $key, 'setting.value' => $value] as $field => $word) {
            if (trim($word) === '') {
                throw ThePluginCatalogueIsUnreadable::blank($field);
            }
        }

        return new self(trim($key), trim($value));
    }

    public function key(): string
    {
        return $this->key;
    }

    /** The value that turns reaching the catalogue on. */
    public function value(): string
    {
        return $this->value;
    }
}
