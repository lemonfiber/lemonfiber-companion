<?php

declare(strict_types=1);

use Tests\Support\Module;

// Every action this application sends names the attempt it is part of.
//
// A key on every call, the ones that only describe what they would do included,
// is a rule nobody has to remember: a key on a call that changes nothing is
// harmless, and a rule that asked which calls change a stack left seven of them
// without one, the update among them. So the question is asked of the source,
// of every call that sends an action, and it is the same question each time.

/**
 * Every call in this source that sends an action and names no attempt, by line.
 *
 * `act()` takes the endpoint, the body and the key, and `repair()` the repair
 * and the key. `repair()` with nothing between its brackets is the getter a
 * confirmation answers with, not a call that sends.
 *
 * @return list<int>
 */
function sendsWithoutAKey(string $source): array
{
    $tokens = array_values(PhpToken::tokenize($source));
    $unnamed = [];

    for ($at = 1, $last = count($tokens) - 1; $at < $last; $at++) {
        $wants = ['act' => 2, 'repair' => 2][$tokens[$at]->text] ?? 0;
        $isACall = $tokens[$at - 1]->text === '->' && $tokens[$at + 1]->text === '(';
        $given = $isACall ? argumentsAfter($tokens, $at + 1) : 0;

        if ($given > 0 && $given < $wants) {
            $unnamed[] = $tokens[$at]->line;
        }
    }

    return $unnamed;
}

/**
 * How many arguments the call opening at this bracket is given.
 *
 * @param list<PhpToken> $tokens
 */
function argumentsAfter(array $tokens, int $opening): int
{
    $arguments = [''];
    $depth = 0;

    for ($at = $opening + 1, $last = count($tokens); $at < $last && $depth >= 0; $at++) {
        $depth += howDeepItGoes($tokens[$at]->text);

        if ($depth === 0 && $tokens[$at]->text === ',') {
            $arguments[] = '';

            continue;
        }

        $arguments[count($arguments) - 1] .= $depth >= 0 ? $tokens[$at]->text : '';
    }

    return count(array_filter($arguments, static fn(string $one): bool => trim($one) !== ''));
}

/** How much deeper into brackets this token goes, or how far out. */
function howDeepItGoes(string $text): int
{
    return match ($text) {
        '(', '[', '{' => 1,
        ')', ']', '}' => -1,
        default => 0,
    };
}

it('has every action name the attempt it is part of', function (): void {
    $read = [];
    $unnamed = [];

    foreach (Module::all() as $module) {
        foreach ($module->classes() as $file) {
            $read[] = $file;

            foreach (sendsWithoutAKey((string) file_get_contents($file)) as $line) {
                $unnamed[] = sprintf('%s:%d', $file, $line);
            }
        }
    }

    expect($read)->not->toBe([], 'no source was read, so this rule found nothing')
        ->and($unnamed)->toBe([], sprintf(
            "These send an action and name no attempt:\n  %s\n\n"
            . 'A key is what lets an action sent again, where its answer was lost, be one act '
            . 'rather than two. Mint one where the action is sent, from `Entropy`, and pass '
            . 'it as the last argument (N1-R42).',
            implode("\n  ", $unnamed),
        ));
});

it('finds an action that names no attempt however its call is laid out', function (): void {
    // The judgement, handed each shape. A rule that read only one layout would
    // pass a call written the other way, which is how the seven went unseen.
    expect(sendsWithoutAKey('<?php $client->act(new UpAction(forms: [1, 2]));'))->toBe([1])
        ->and(sendsWithoutAKey("<?php \$client->act(\n    new UpAction(\n        forms: [f(1, 2)],\n    ),\n);"))->toBe([1])
        ->and(sendsWithoutAKey('<?php $client->act($action);'))->toBe([1])
        ->and(sendsWithoutAKey('<?php $client->repair(Asking::offer());'))->toBe([1])
        ->and(sendsWithoutAKey('<?php $client->act(new UpAction(forms: [1, 2]), $key);'))->toBe([])
        ->and(sendsWithoutAKey("<?php \$client->act(\n    new UpAction(\n        forms: [],\n    ),\n    \$key,\n);"))->toBe([])
        ->and(sendsWithoutAKey('<?php $client->repair($asked, $key);'))->toBe([])
        ->and(sendsWithoutAKey('<?php $confirmed->repair()->answers();'))->toBe([])
        ->and(sendsWithoutAKey('<?php function act($a) {} act(1);'))->toBe([]);
});
