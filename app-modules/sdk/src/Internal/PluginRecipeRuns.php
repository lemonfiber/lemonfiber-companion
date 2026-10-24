<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARecipeRun;
use Modules\Kernel\Api\AStepThatRan;
use Modules\Kernel\Api\TheStepsThatRan;
use Modules\Kernel\Api\WhatARecipeRunDid;
use Modules\Kernel\Api\WhatAStepCameTo;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * The `demand` account of the `plugins` envelope, as running one of a plugin's demand recipes.
 *
 * A reading carries the plugin and the recipe; a run carries what the recipe
 * came to beside them, read step by step. Anything the kernel would refuse is
 * refused here, by position, never salvaged.
 */
final readonly class PluginRecipeRuns
{
    /**
     * What the stack said of running the recipe.
     *
     * @param array<mixed> $demand
     */
    public static function in(array $demand): ARecipeRun
    {
        $plugin = APluginId::called(Required::text($demand, WireField::Plugin, PluginsAreUnreadable::missing(WireField::Plugin)));
        $recipe = PluginRecords::recipe(self::table($demand, PluginsField::Recipe), 0);

        if (! array_key_exists(PluginsField::Ran->value, $demand) || $demand[PluginsField::Ran->value] === null) {
            return ARecipeRun::rehearsed($plugin, $recipe);
        }

        return ARecipeRun::ran($plugin, $recipe, self::did(self::table($demand, PluginsField::Ran)));
    }

    /**
     * What the run came to.
     *
     * @param array<mixed> $ran
     */
    private static function did(array $ran): WhatARecipeRunDid
    {
        $steps = [];
        $at = 0;

        foreach (Required::rows($ran, WireField::Steps, PluginsAreUnreadable::missing(WireField::Steps)) as $step) {
            if (! is_array($step)) {
                throw PluginsAreUnreadable::entry(WireField::Steps, WireField::Step, $at);
            }

            $steps[] = self::step($step, $at);
            $at++;
        }

        return WhatARecipeRunDid::reported(
            Required::flag($ran, WireField::Held, PluginsAreUnreadable::missing(WireField::Held)),
            self::optional($ran, WireField::Why),
            TheStepsThatRan::these(...$steps),
        );
    }

    /**
     * One step, and what it came to.
     *
     * @param array<mixed> $step
     */
    private static function step(array $step, int $at): AStepThatRan
    {
        $came = Required::text($step, PluginsField::Came, PluginsAreUnreadable::entry(WireField::Steps, PluginsField::Came, $at));

        return AStepThatRan::reported(
            Required::text($step, WireField::Step, PluginsAreUnreadable::entry(WireField::Steps, WireField::Step, $at)),
            Required::text($step, WireField::To, PluginsAreUnreadable::entry(WireField::Steps, WireField::To, $at)),
            WhatAStepCameTo::tryFrom($came) ?? throw PluginsAreUnreadable::said(PluginsField::Came, $came),
            self::optional($step, WireField::Why),
        );
    }

    /**
     * A table the account has to carry.
     *
     * @param  array<mixed> $table
     * @return array<mixed>
     */
    private static function table(array $table, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $table) || ! is_array($table[$field->value])) {
            throw PluginsAreUnreadable::missing($field);
        }

        return $table[$field->value];
    }

    /**
     * A word the account may leave out or send as null, as empty where it did; text of any other type is refused.
     *
     * @param array<mixed> $table
     */
    private static function optional(array $table, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $table) || $table[$field->value] === null) {
            return '';
        }

        if (! is_string($table[$field->value])) {
            throw PluginsAreUnreadable::missing($field);
        }

        return $table[$field->value];
    }
}
