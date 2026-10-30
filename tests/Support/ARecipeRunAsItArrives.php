<?php

declare(strict_types=1);

namespace Tests\Support;

use function is_array;

use Modules\Kernel\Api\AnInputARecipeAsks;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARecipe;
use Modules\Kernel\Api\ARecipeRun;
use Modules\Kernel\Api\ARecipeStep;
use Modules\Kernel\Api\AStepThatRan;
use Modules\Kernel\Api\AValueItCarries;
use Modules\Kernel\Api\TheInputsARecipeAsks;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\TheRecipeSteps;
use Modules\Kernel\Api\TheStepsThatRan;
use Modules\Kernel\Api\TheValuesItCarries;
use Modules\Kernel\Api\WhatARecipeRunDid;
use Modules\Kernel\Api\WhatAStepCameTo;
use Modules\Kernel\Api\WhenARecipeRuns;

/**
 * Tdarr with two recipes it runs only when asked, one asking the operator for a value, and running the other, on the wire and as this app holds them.
 *
 * Beside {@see APluginAsItArrives}, whose install recipe it keeps: the
 * install's approvals are that recipe's alone, and a run asks for its own.
 * Every word is an ordinary one: nothing here is a secret.
 */
final readonly class ARecipeRunAsItArrives
{
    /** The approval the demand recipe's one pair asks for. */
    public const string APPROVAL = 'token@plex.tv';

    /**
     * The recipe it runs on demand, as the record holds it.
     *
     * @return array<string, mixed>
     */
    public static function theRecipeOnTheWire(): array
    {
        return [
            'id' => 'resync',
            'title' => 'Ask Plex to look again',
            'why' => 'So what Tdarr finished shows up',
            'on' => 'demand',
            'steps' => [['id' => 'nudge', 'method' => 'POST', 'to' => 'plex.tv', 'path' => '/refresh', 'adapter' => null]],
            'pairs' => [['value' => 'token', 'origin' => 'credential-store', 'to' => 'plex.tv', 'approval' => self::APPROVAL]],
        ];
    }

    /** The recipe it runs on demand, as this app holds it. */
    public static function theRecipe(): ARecipe
    {
        return ARecipe::of(
            'resync',
            'Ask Plex to look again',
            'So what Tdarr finished shows up',
            TheRecipeSteps::these(ARecipeStep::calling('nudge', 'POST', 'plex.tv', '/refresh', '')),
            TheValuesItCarries::these(AValueItCarries::listed('token', 'credential-store', 'plex.tv', self::APPROVAL, '', '')),
            WhenARecipeRuns::Demand,
            TheInputsARecipeAsks::these(),
        );
    }

    /**
     * The recipe it runs on demand that asks the operator for a value, as the record holds it.
     *
     * @return array<string, mixed>
     */
    public static function theAskingRecipeOnTheWire(): array
    {
        return [
            'id' => 'pin',
            'title' => 'Pin a library to a node',
            'why' => 'So each node takes the work it suits',
            'on' => 'demand',
            'inputs' => [['name' => 'node', 'ask' => 'Which node should take the library', 'secret' => false]],
            'steps' => [],
            'pairs' => [],
        ];
    }

    /** The recipe it runs on demand that asks the operator for a value, as this app holds it. */
    public static function theAskingRecipe(): ARecipe
    {
        return ARecipe::of(
            'pin',
            'Pin a library to a node',
            'So each node takes the work it suits',
            TheRecipeSteps::these(),
            TheValuesItCarries::these(),
            WhenARecipeRuns::Demand,
            TheInputsARecipeAsks::these(AnInputARecipeAsks::named('node', 'Which node should take the library')),
        );
    }

    /**
     * Tdarr with its install recipe and both demand recipes, as the record holds it.
     *
     * @return array<string, mixed>
     */
    public static function thePluginOnTheWire(): array
    {
        $plugin = APluginAsItArrives::onTheWire();
        $recipes = is_array($plugin['recipes']) ? $plugin['recipes'] : [];

        return [...$plugin, 'recipes' => [...$recipes, self::theRecipeOnTheWire(), self::theAskingRecipeOnTheWire()]];
    }

    /** Tdarr with its install recipe and both demand recipes, as this app holds it. */
    public static function thePlugin(): APlugin
    {
        $held = APluginAsItArrives::held();
        $recipes = [];

        foreach ($held->recipes() as $recipe) {
            $recipes[] = $recipe;
        }

        return APlugin::named($held->id(), $held->version(), $held->shown(), $held->vouched(), TheRecipes::these(...$recipes, ...[self::theRecipe(), self::theAskingRecipe()]));
    }

    /**
     * The stack's answer about running the recipe: the rehearsal, or what it came to.
     *
     * @return array<string, mixed>
     */
    public static function theAnswerOnTheWire(bool $ran, bool $held = true): array
    {
        $answer = APluginAsItArrives::theAnswerAbout('demand', [
            'plugin' => 'tdarr',
            'recipe' => self::theRecipeOnTheWire(),
            'ran' => $ran ? [
                'recipe' => 'resync',
                'held' => $held,
                'why' => $held ? null : 'Plex would not answer',
                'steps' => [['step' => 'nudge', 'to' => 'plex.tv', 'came' => $held ? 'answered' : 'unreachable', 'status' => $held ? 200 : null, 'tries' => 1, 'landed' => $held, 'why' => $held ? null : 'No answer in time']],
            ] : null,
        ]);
        $data = is_array($answer['data']) ? $answer['data'] : [];
        $data['installed'] = [self::thePluginOnTheWire()];

        if ($ran) {
            unset($data['agreement']);
        }

        return [...$answer, 'data' => $data];
    }

    /** The stack's answer about running the recipe, as this app holds it. */
    public static function theAnswer(bool $ran, bool $held = true, ?string $agreement = null): ThePlugins
    {
        $run = $ran
            ? ARecipeRun::ran(APluginId::called('tdarr'), self::theRecipe(), WhatARecipeRunDid::reported(
                $held,
                $held ? '' : 'Plex would not answer',
                TheStepsThatRan::these(AStepThatRan::reported('nudge', 'plex.tv', $held ? WhatAStepCameTo::Answered : WhatAStepCameTo::Unreachable, $held ? '' : 'No answer in time')),
            ))
            : ARecipeRun::rehearsed(APluginId::called('tdarr'), self::theRecipe());

        return ThePlugins::aboutARecipeRun(
            TheInstalledPlugins::these(self::thePlugin()),
            ThePluginSources::these(),
            $agreement ?? ($ran ? '' : APluginAsItArrives::AGREEMENT),
            $run,
        );
    }
}
