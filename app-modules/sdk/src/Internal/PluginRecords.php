<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\ARecipeStep;
use Modules\Kernel\Api\AValueItCarries;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\TheRecipeSteps;
use Modules\Kernel\Api\TheValuesItCarries;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * One plugin as the `plugins` envelope records it: what it is, what vouches for it, and its recipes.
 *
 * Written the way {@see TheReversal} is: a static fold with no state,
 * refusing rather than salvaging. Apart from {@see PluginInstalls} because the
 * same record arrives in two places, every installed plugin and the plugin an
 * install would settle, and is read once.
 *
 * **What the record may leave out reads as said nothing.** The core defaults
 * every provenance word, so a record written before one was kept reads as a
 * plugin that declared nothing, and an absent `reviewed` is a plugin nobody
 * reviewed. The id, the version and every list a recipe holds may not be left
 * out: a recipe one step or one pair short is something the operator agrees
 * to without being told.
 */
final readonly class PluginRecords
{
    /**
     * One plugin.
     *
     * @param array<mixed> $row
     */
    public static function of(array $row, NamesAWireField $list, int $position): APlugin
    {
        return APlugin::named(
            self::text($row, $list, PluginsField::Plugin, $position),
            self::text($row, $list, WireField::Version, $position),
            self::optional($row, WireField::Name),
            self::vouched($row),
            TheRecipes::these(...self::recipes($row, $position)),
        );
    }

    /**
     * Where it came from and what vouches for it.
     *
     * @param array<mixed> $row
     */
    private static function vouched(array $row): WhatVouchesForAPlugin
    {
        $declared = array_key_exists(PluginsField::Declared->value, $row) && is_array($row[PluginsField::Declared->value])
            ? $row[PluginsField::Declared->value]
            : [];

        return WhatVouchesForAPlugin::recorded(
            self::optional($row, WireField::From),
            self::optional($row, PluginsField::Revision),
            self::optional($row, PluginsField::Signed),
            self::reviewed($declared),
            self::optional($declared, WireField::Upstream),
            self::optional($declared, WireField::License),
        );
    }

    /**
     * Whether anybody reviewed it; absent is nobody did, and anything but true or false is refused.
     *
     * @param array<mixed> $declared
     */
    private static function reviewed(array $declared): bool
    {
        if (! array_key_exists(PluginsField::Reviewed->value, $declared)) {
            return false;
        }

        if (! is_bool($declared[PluginsField::Reviewed->value])) {
            throw PluginsAreUnreadable::missing(PluginsField::Reviewed);
        }

        return $declared[PluginsField::Reviewed->value];
    }

    /**
     * Every recipe, in the manifest's order; none where the record keeps none.
     *
     * @param  array<mixed>  $row
     * @return list<ARecipe>
     */
    private static function recipes(array $row, int $position): array
    {
        if (! array_key_exists(PluginsField::Recipes->value, $row)) {
            return [];
        }

        if (! is_array($row[PluginsField::Recipes->value])) {
            throw PluginsAreUnreadable::entry(PluginsField::Recipes, PluginsField::Recipes, $position);
        }

        $found = [];
        $at = 0;

        foreach ($row[PluginsField::Recipes->value] as $recipe) {
            if (! is_array($recipe)) {
                throw PluginsAreUnreadable::entry(PluginsField::Recipes, WireField::Id, $at);
            }

            $found[] = ARecipe::of(
                self::text($recipe, PluginsField::Recipes, WireField::Id, $at),
                self::text($recipe, PluginsField::Recipes, WireField::Title, $at),
                self::optional($recipe, WireField::Why),
                TheRecipeSteps::these(...self::steps($recipe)),
                TheValuesItCarries::these(...self::pairs($recipe)),
            );
            $at++;
        }

        return $found;
    }

    /**
     * Every call a recipe makes, in its order.
     *
     * @param  array<mixed>      $recipe
     * @return list<ARecipeStep>
     */
    private static function steps(array $recipe): array
    {
        $found = [];
        $at = 0;

        foreach (self::rows($recipe, WireField::Steps) as $step) {
            if (! is_array($step)) {
                throw PluginsAreUnreadable::entry(WireField::Steps, WireField::Id, $at);
            }

            $found[] = ARecipeStep::calling(
                self::text($step, WireField::Steps, WireField::Id, $at),
                self::text($step, WireField::Steps, PluginsField::Method, $at),
                self::text($step, WireField::Steps, WireField::To, $at),
                self::text($step, WireField::Steps, WireField::Path, $at),
                self::adapter($step),
            );
            $at++;
        }

        return $found;
    }

    /**
     * Which of lemonfiber's adapters a call reaches through, or empty where none does.
     *
     * @param array<mixed> $step
     */
    private static function adapter(array $step): string
    {
        if (! array_key_exists(PluginsField::Adapter->value, $step) || ! is_array($step[PluginsField::Adapter->value])) {
            return '';
        }

        return self::optional($step[PluginsField::Adapter->value], WireField::Kind);
    }

    /**
     * Every value a recipe could carry, and where to.
     *
     * @param  array<mixed>          $recipe
     * @return list<AValueItCarries>
     */
    private static function pairs(array $recipe): array
    {
        $found = [];
        $at = 0;

        foreach (self::rows($recipe, PluginsField::Pairs) as $pair) {
            if (! is_array($pair)) {
                throw PluginsAreUnreadable::entry(PluginsField::Pairs, WireField::Value, $at);
            }

            $found[] = AValueItCarries::listed(
                self::text($pair, PluginsField::Pairs, WireField::Value, $at),
                self::optional($pair, WireField::Origin),
                self::text($pair, PluginsField::Pairs, WireField::To, $at),
                self::optional($pair, PluginsField::Approval),
                self::optional($pair, PluginsField::Release),
                self::optional($pair, WireField::From),
            );
            $at++;
        }

        return $found;
    }

    /**
     * The rows of one list a recipe must carry.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $data) || ! is_array($data[$list->value])) {
            throw PluginsAreUnreadable::missing($list);
        }

        return $data[$list->value];
    }

    /**
     * A word a row must carry, as text an operator can be shown.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw PluginsAreUnreadable::entry($list, $field, $position);
        }

        return $row[$field->value];
    }

    /**
     * A word a row may leave out or send as null, as empty where it did.
     *
     * Text of any other type is refused: an answer that says something this
     * app cannot read is not the same as one that says nothing.
     *
     * @param array<mixed> $row
     */
    private static function optional(array $row, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $row) || $row[$field->value] === null) {
            return '';
        }

        if (! is_string($row[$field->value])) {
            throw PluginsAreUnreadable::missing($field);
        }

        return $row[$field->value];
    }
}
