<?php

declare(strict_types=1);

use Lemonfiber\Sdk\ActionRequest;
use Lemonfiber\Sdk\Repair;
use Tests\Support\Tree;

/**
 * Every action another surface can take is one the app sends, except where a
 * requirement says otherwise and why.
 *
 * The sibling of `EveryActionTheStackOffersTest`, which asks it of the kinds a
 * stack answers with. This asks it of what a stack can be told to do: every
 * action the SDK generates from the contract, each one a request class. Four
 * lists cover them between them, and each list is held to being true, so an
 * action the contract gains arrives unclassified and fails saying which.
 */

/**
 * Actions the app sends through a class of the SDK's that builds the request itself.
 *
 * A repair is agreed to through `Repair`, which carries the offer and what was
 * agreed and builds the `repair` request, so no class here names the request.
 *
 * @var array<string, class-string>
 */
const SENT_THROUGH_THE_SDK = [
    'repair' => Repair::class,
];

/**
 * Actions the app deliberately does not send, each with the requirement saying why.
 *
 * As narrow as its sibling's: an entry needs a requirement, not a judgement.
 *
 * @var array<string, string>
 */
const NOT_SENT_BECAUSE = [
    'quality-reapply' => 'N24-R5 — its whole effect is overwriting a quality configuration the operator edited by hand, which the app does not offer (N19-R4)',
];

/**
 * Actions nobody has offered yet.
 *
 * Moving one out is the work: building the screen that sends it, or writing the
 * requirement that says why the app does not.
 */
const NOT_SENT_YET = [
    'internal-certificate-replace',
    'space',
];

/**
 * Every action the contract lists, by its name, with the request class that sends it.
 *
 * @return array<string, class-string<ActionRequest>>
 */
function everyActionTheContractLists(): array
{
    $actions = [];

    foreach (Tree::filesUnder(Tree::at('vendor/lemonfiber/sdk-php/src/Generated'), 'Action.php') as $file) {
        $class = sprintf('Lemonfiber\\Sdk\\Generated\\%s', basename($file, '.php'));

        if (is_subclass_of($class, ActionRequest::class)) {
            $actions[new ReflectionClass($class)->newInstanceWithoutConstructor()->action()] = $class;
        }
    }

    ksort($actions);

    return $actions;
}

/** Whether the SDK adapter names this class anywhere, as a word. */
function theAdapterNames(string $class): bool
{
    $short = (string) mb_strrchr($class, '\\');
    $short = mb_substr($short, 1);
    return array_any(Tree::filesUnder(Tree::at('app-modules/sdk/src'), '.php'), fn(string $file): bool => preg_match(sprintf('/\b%s\b/', preg_quote($short, '/')), (string) file_get_contents($file)) === 1);
}

it('has looked at every action the contract lists', function (): void {
    $actions = everyActionTheContractLists();
    $sent = array_keys(array_filter($actions, theAdapterNames(...)));
    $unclassified = array_values(array_diff(array_keys($actions), $sent, array_keys(SENT_THROUGH_THE_SDK), array_keys(NOT_SENT_BECAUSE), NOT_SENT_YET));

    expect($actions)->not->toBe([], 'no action was found in the SDK, so this rule read nothing')
        ->and($unclassified)->toBe([], sprintf(
            "These actions are neither sent nor waiting:\n  %s\n\nSend each, name the requirement that keeps it off the phone in NOT_SENT_BECAUSE, or name it in NOT_SENT_YET (N1-R2).\n",
            implode("\n  ", $unclassified),
        ));
});

it('names as waiting only actions the app does not send, and only ones the contract lists', function (): void {
    $actions = everyActionTheContractLists();
    $sentAfterAll = array_values(array_filter([...NOT_SENT_YET, ...array_keys(NOT_SENT_BECAUSE)], static fn(string $action): bool => array_key_exists($action, $actions) && theAdapterNames($actions[$action])));
    $gone = array_values(array_diff([...NOT_SENT_YET, ...array_keys(NOT_SENT_BECAUSE), ...array_keys(SENT_THROUGH_THE_SDK)], array_keys($actions)));

    expect($sentAfterAll)->toBe([], 'these are sent now; take them out of the list that says they are not (N1-R2)')
        ->and($gone)->toBe([], 'the contract no longer lists these; take them out (N1-R2)');
});

it('sends each action it says goes through the SDK', function (string $action, string $through): void {
    expect(theAdapterNames($through))->toBeTrue(sprintf('nothing in the SDK adapter names %s, which sends `%s`', $through, $action));
})->with(array_map(null, array_keys(SENT_THROUGH_THE_SDK), array_values(SENT_THROUGH_THE_SDK)));
