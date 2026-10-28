<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use Closure;

use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\AnInputARecipeAsks;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\ARecipeStep;
use Modules\Kernel\Api\AValueItCarries;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheCredentialGrants;
use Modules\Kernel\Api\TheInputsARecipeAsks;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\TheRecipeSteps;
use Modules\Kernel\Api\TheValuesItCarries;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Modules\Kernel\Api\WhenARecipeRuns;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

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
     * One plugin, refused in the words of where it sits.
     *
     * @param array<mixed>                                    $row
     * @param Closure(NamesAWireField): PluginsAreUnreadable $unreadable
     */
    public static function of(array $row, Closure $unreadable): APlugin
    {
        $plugin = APlugin::named(
            Required::text($row, WireField::Plugin, $unreadable(WireField::Plugin)),
            Required::text($row, WireField::Version, $unreadable(WireField::Version)),
            self::optional($row, WireField::Name),
            self::vouched($row),
            TheRecipes::these(...self::recipes($row, $unreadable)),
            ...self::services($row),
        );

        return $plugin->holding(TheCredentialGrants::of(...GrantRows::onTheRecord($row, APluginId::called($plugin->id()))));
    }

    /**
     * One recipe as the record or a reading carries it, with when it runs and every value it asks the operator for, refused in the words of where it sits.
     *
     * @param array<mixed>                                    $recipe
     * @param Closure(NamesAWireField): PluginsAreUnreadable $unreadable
     */
    public static function recipe(array $recipe, Closure $unreadable): ARecipe
    {
        return ARecipe::of(
            Required::text($recipe, WireField::Id, $unreadable(WireField::Id)),
            Required::text($recipe, WireField::Title, $unreadable(WireField::Title)),
            self::optional($recipe, WireField::Why),
            TheRecipeSteps::these(...self::steps($recipe)),
            TheValuesItCarries::these(...self::pairs($recipe)),
            self::runs($recipe, $unreadable),
            TheInputsARecipeAsks::these(...self::inputs($recipe)),
        );
    }


    /**
     * Each service the plugin placed, by its id, in the record's order.
     *
     * @param  array<mixed>    $row
     * @return list<ServiceId>
     */
    private static function services(array $row): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(self::rows($row, WireField::Services)) as $at => $placed) {
            if (! is_array($placed)) {
                throw PluginsAreUnreadable::entry(WireField::Services, WireField::Service, $at);
            }

            $found[] = ServiceId::called(self::text($placed, WireField::Services, WireField::Service, $at));
        }

        return $found;
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
     * @param  array<mixed>                                    $row
     * @param  Closure(NamesAWireField): PluginsAreUnreadable $unreadable
     * @return list<ARecipe>
     */
    private static function recipes(array $row, Closure $unreadable): array
    {
        if (! array_key_exists(PluginsField::Recipes->value, $row)) {
            return [];
        }

        if (! is_array($row[PluginsField::Recipes->value])) {
            throw $unreadable(PluginsField::Recipes);
        }

        $found = [];

        foreach (InTheOrderSent::rows($row[PluginsField::Recipes->value]) as $at => $recipe) {
            if (! is_array($recipe)) {
                throw PluginsAreUnreadable::entry(PluginsField::Recipes, WireField::Id, $at);
            }

            $found[] = self::recipe($recipe, static fn(NamesAWireField $field): PluginsAreUnreadable => PluginsAreUnreadable::entry(PluginsField::Recipes, $field, $at));
        }

        return $found;
    }

    /**
     * When a recipe runs; one that does not say runs inside an install, as the contract has it.
     *
     * @param array<mixed>                                    $recipe
     * @param Closure(NamesAWireField): PluginsAreUnreadable $unreadable
     */
    private static function runs(array $recipe, Closure $unreadable): WhenARecipeRuns
    {
        if (! array_key_exists(PluginsField::On->value, $recipe)) {
            return WhenARecipeRuns::Install;
        }

        $said = $recipe[PluginsField::On->value];

        return (is_string($said) ? WhenARecipeRuns::tryFrom($said) : null) ?? throw $unreadable(PluginsField::On);
    }

    /**
     * Every value a recipe asks the operator for; none where it lists none.
     *
     * @param  array<mixed>             $recipe
     * @return list<AnInputARecipeAsks>
     */
    private static function inputs(array $recipe): array
    {
        if (! array_key_exists(PluginsField::Inputs->value, $recipe)) {
            return [];
        }

        $found = [];

        foreach (InTheOrderSent::rows(self::rows($recipe, PluginsField::Inputs)) as $at => $input) {
            if (! is_array($input)) {
                throw PluginsAreUnreadable::entry(PluginsField::Inputs, WireField::Name, $at);
            }

            $found[] = AnInputARecipeAsks::named(
                self::text($input, PluginsField::Inputs, WireField::Name, $at),
                self::text($input, PluginsField::Inputs, PluginsField::Ask, $at),
            );
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

        foreach (InTheOrderSent::rows(self::rows($recipe, WireField::Steps)) as $at => $step) {
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

        foreach (InTheOrderSent::rows(self::rows($recipe, PluginsField::Pairs)) as $at => $pair) {
            if (! is_array($pair)) {
                throw PluginsAreUnreadable::entry(PluginsField::Pairs, WireField::Value, $at);
            }

            $found[] = AValueItCarries::listed(
                self::text($pair, PluginsField::Pairs, WireField::Value, $at),
                self::optional($pair, WireField::Origin),
                self::text($pair, PluginsField::Pairs, WireField::To, $at),
                self::optional($pair, WireField::Approval),
                self::optional($pair, PluginsField::Release),
                self::optional($pair, WireField::From),
            );
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
        return Required::text($row, $field, PluginsAreUnreadable::entry($list, $field, $position));
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
