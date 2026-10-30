<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function is_array;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\WhenARecipeRuns;
use Modules\Sdk\Api\PluginInstalls;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Tests\Support\ARecipeRunAsItArrives;

/**
 * The answer about running the recipe, with its `demand` account changed as a case says.
 *
 * `plugins` is stood in for and not judged: each case changes one field of the answer the contract test judges whole, so the reader can be seen to refuse it.
 *
 * @param array<mixed> $changed
 *
 * @return Envelope<mixed>
 */
function anAnswerWithTheDemand(array $changed, bool $ran = true): Envelope
{
    $answer = ARecipeRunAsItArrives::theAnswerOnTheWire($ran);
    $data = is_array($answer['data']) ? $answer['data'] : [];
    $demand = is_array($data['demand']) ? $data['demand'] : [];

    return new Envelope(1, 'plugins', [...$data, 'demand' => [...$demand, ...$changed]]);
}

/**
 * The answer about running the recipe, with the step that ran changed as a case says.
 *
 * @param array<mixed> $changed
 *
 * @return Envelope<mixed>
 */
function anAnswerWithTheStep(array $changed): Envelope
{
    $step = ['step' => 'nudge', 'to' => 'plex.tv', 'came' => 'answered', 'status' => 200, 'tries' => 1, 'landed' => true, 'why' => null];

    return anAnswerWithTheDemand(['ran' => ['recipe' => 'resync', 'held' => true, 'why' => null, 'steps' => [[...$step, ...$changed]]]]);
}

/**
 * The listing, holding Tdarr with its demand recipe changed as a case says.
 *
 * @param array<string, mixed> $changed
 *
 * @return Envelope<mixed>
 */
function aListingWithTheRecipe(array $changed): Envelope
{
    $plugin = ARecipeRunAsItArrives::thePluginOnTheWire();
    $plugin['recipes'] = [[...ARecipeRunAsItArrives::theRecipeOnTheWire(), ...$changed]];

    return new Envelope(1, 'plugins', ['installed' => [$plugin], 'rehearsed' => false, 'sources' => [], 'nonconforming' => []]);
}

it('refuses a run account missing what it is about, or carrying something other than what it ran', function (array $changed, string $refused): void {
    expect(static fn(): ThePlugins => PluginInstalls::in(anAnswerWithTheDemand($changed)))->toThrow(PluginsAreUnreadable::class, $refused);
})->with([
    'no plugin' => [['plugin' => null], '`plugin`'],
    'a recipe that is not one' => [['recipe' => 'resync'], '`recipe`'],
    'a run that is not one' => [['ran' => 'yes'], '`ran`'],
    'a run with no steps' => [['ran' => ['recipe' => 'resync', 'held' => true]], '`steps`'],
    'a run that does not say whether it held' => [['ran' => ['recipe' => 'resync', 'steps' => []]], '`held`'],
    'a step that is not one' => [['ran' => ['recipe' => 'resync', 'held' => true, 'steps' => ['nudge']]], '`step`'],
    'a reason that is not text' => [['ran' => ['recipe' => 'resync', 'held' => false, 'why' => 3, 'steps' => []]], '`why`'],
    'a recipe with no title' => [['recipe' => [...ARecipeRunAsItArrives::theRecipeOnTheWire(), 'title' => ' ']], '`recipe` in the plugins envelope has no readable `title`.'],
    'a recipe run at no word this app knows' => [['recipe' => [...ARecipeRunAsItArrives::theRecipeOnTheWire(), 'on' => 'whenever']], '`recipe` in the plugins envelope has no readable `on`.'],
]);

it('refuses a step missing what it is, where it calls or what it came to, or naming an outcome this app has no word for', function (array $changed, string $refused): void {
    expect(static fn(): ThePlugins => PluginInstalls::in(anAnswerWithTheStep($changed)))->toThrow(PluginsAreUnreadable::class, $refused);
})->with([
    'no step' => [['step' => ' '], '`step`'],
    'nowhere it calls' => [['to' => null], '`to`'],
    'no outcome' => [['came' => null], '`came`'],
    'an outcome with no word here' => [['came' => 'exploded'], 'exploded'],
    'a reason that is not text' => [['why' => ['no']], '`why`'],
]);

it('reads a recipe that does not say when it runs as one an install runs, and refuses a word it does not know', function (): void {
    $recipe = ARecipeRunAsItArrives::theRecipeOnTheWire();
    unset($recipe['on']);
    $plugin = ARecipeRunAsItArrives::thePluginOnTheWire();
    $plugin['recipes'] = [$recipe];
    $listed = PluginInstalls::in(new Envelope(1, 'plugins', ['installed' => [$plugin], 'rehearsed' => false, 'sources' => [], 'nonconforming' => []]));
    $runs = [];

    foreach ($listed->installed() as $installed) {
        foreach ($installed->recipes() as $one) {
            $runs[] = $one->runs();
        }
    }

    expect($runs)->toBe([WhenARecipeRuns::Install])
        ->and(static fn(): ThePlugins => PluginInstalls::in(aListingWithTheRecipe(['on' => 'sometimes'])))->toThrow(PluginsAreUnreadable::class, '`on`')
        ->and(static fn(): ThePlugins => PluginInstalls::in(aListingWithTheRecipe(['on' => 2])))->toThrow(PluginsAreUnreadable::class, '`on`');
});

it('reads what a recipe asks for, and refuses an input that will not say its name or what it asks', function (): void {
    $listed = PluginInstalls::in(aListingWithTheRecipe(['inputs' => [['name' => 'node', 'ask' => 'Which node', 'secret' => true]]]));
    $asked = [];

    foreach ($listed->installed() as $installed) {
        foreach ($installed->recipes() as $one) {
            foreach ($one->asks() as $input) {
                $asked[] = [$input->name(), $input->ask()];
            }
        }
    }

    expect($asked)->toBe([['node', 'Which node']])
        ->and(static fn(): ThePlugins => PluginInstalls::in(aListingWithTheRecipe(['inputs' => 'node'])))->toThrow(PluginsAreUnreadable::class, '`inputs`')
        ->and(static fn(): ThePlugins => PluginInstalls::in(aListingWithTheRecipe(['inputs' => ['node']])))->toThrow(PluginsAreUnreadable::class, '`name`')
        ->and(static fn(): ThePlugins => PluginInstalls::in(aListingWithTheRecipe(['inputs' => [['name' => 'node']]])))->toThrow(PluginsAreUnreadable::class, '`ask`');
});
