<?php

declare(strict_types=1);

use Tests\Support\Tree;

// G11 — a diagnostic fails the run, and no setting exempts one.
//
// A warning is a gate only where something acts on it. PHPUnit's `failOn*`
// attributes are that something, and they are read out of `phpunit.xml` — a
// file a single attribute can be deleted from, in a diff that looks like
// tidying, with nothing else in the repository noticing. Every other rule here
// then keeps holding and the run keeps reporting a number that is no longer
// connected to it.
//
// **The two halves are separate settings and either alone is a gate that is
// not one.** `failOnWarning` acts on what reaches the result; PHPUnit's issue
// filter decides what reaches it, and by default a diagnostic raised inside
// `@` does not — it is printed and then dropped, so the summary counts warnings
// and the process exits zero. `ignoreSuppressionOf*` on `<source>`
// is what puts the suppressed ones back in front of `failOn*`. Turning either
// off restores the silence without changing the other, which is why this reads
// both and why removing one attribute has to fail here.
//
// Note the direction: these settings make PHPUnit *report* a diagnostic PHP
// raised anyway. Nothing here raises one, and nothing here changes what the
// application does at runtime — `@` still suppresses in production exactly as
// it did.

/**
 * The attributes one element of `phpunit.xml` declares.
 *
 * Parsed rather than matched as text: an attribute is a name and a value in a
 * specific element, and a text scan for `failOnWarning="true"` would be
 * satisfied by the same string inside the comment that explains it.
 *
 * @return array<string, string> attribute => value
 */
function settingsDeclaredAt(string $element): array
{
    $configuration = simplexml_load_file(Tree::at('phpunit.xml'));

    if ($configuration === false) {
        throw new RuntimeException(
            'phpunit.xml could not be parsed, so none of the settings below were read. '
            . 'A checker that finds nothing to check is the failure it exists to prevent.',
        );
    }

    $found = $configuration->xpath($element);

    if (! is_array($found) || $found === []) {
        throw new RuntimeException(sprintf(
            'phpunit.xml holds no `%s` element. The settings this rule is about live '
            . 'there, so their absence is not a pass.',
            $element,
        ));
    }

    $attributes = $found[0]->attributes();

    if ($attributes === null) {
        return [];
    }

    $declared = [];

    foreach ($attributes as $name => $value) {
        $declared[$name] = (string) $value;
    }

    return $declared;
}

/**
 * The settings among a required list that are missing or switched off.
 *
 * @param  array<string, string>  $declared
 * @param  list<string>  $required
 * @return list<string>
 */
function notTurnedOn(array $declared, array $required): array
{
    $found = [];

    foreach ($required as $setting) {
        if (! array_key_exists($setting, $declared)) {
            $found[] = sprintf('%s is not declared', $setting);

            continue;
        }

        if ($declared[$setting] !== 'true') {
            $found[] = sprintf('%s is "%s"', $setting, $declared[$setting]);
        }
    }

    return $found;
}

it('G11 — the reading that decides both of those can say no', function (): void {
    // The two rules below have only ever been asked about a file where the answer
    // is "nothing missing", and everything they demonstrate in that state is
    // equally true of a function that answers `[]` whatever it is handed. There is
    // no file to plant: the settings live in the `phpunit.xml` of the run doing the
    // reading, and taking an attribute out of it changes that run rather than a
    // fixture. So the judgement is handed the violation directly.
    $wanted = ['failOnWarning', 'failOnDeprecation'];

    expect(notTurnedOn(['failOnWarning' => 'true', 'failOnDeprecation' => 'true'], $wanted))
        ->toBe([]);

    // Missing outright, and present but switched off. PHPUnit treats those the
    // same and so does the rule, but they are different mistakes — one is a
    // deletion, the other is somebody turning a gate off on purpose — so the
    // message says which.
    expect(notTurnedOn(['failOnWarning' => 'true'], $wanted))
        ->toBe(['failOnDeprecation is not declared']);
    expect(notTurnedOn(['failOnWarning' => 'true', 'failOnDeprecation' => 'false'], $wanted))
        ->toBe(['failOnDeprecation is "false"']);

    // Every one of them, when the element carries nothing at all — which is what a
    // `<phpunit>` tag stripped of its attributes looks like.
    expect(notTurnedOn([], $wanted))->toHaveCount(2);

    // And a setting nothing asked about is not a finding. The two rules below pass
    // different lists on purpose, and a reading that reported everything it saw
    // would make each of them fail on the other's settings.
    expect(notTurnedOn(['failOnSkipped' => 'false'], ['failOnWarning']))
        ->toBe(['failOnWarning is not declared']);
});

it('G11 — a reported diagnostic ends the run', function (): void {
    // Everything PHPUnit can be told to act on that this suite treats as a
    // defect. `failOnSkipped` and `failOnIncomplete` are deliberately absent:
    // G6 permits a skipped test that gives a reason, and a rule here would
    // refuse what a rule there allows.
    $missing = notTurnedOn(settingsDeclaredAt('/phpunit'), [
        'failOnRisky',
        'failOnWarning',
        'failOnDeprecation',
        'failOnNotice',
        'failOnPhpunitDeprecation',
        'failOnPhpunitNotice',
        'failOnPhpunitWarning',
    ]);

    expect($missing)->toBe([], sprintf(
        "phpunit.xml no longer acts on these:\n  %s\n\n"
        . 'Without them a diagnostic is printed and survived. It scrolls past in CI, '
        . 'where nobody is reading a passing job, and the number in the summary stops '
        . 'meaning anything a gate is connected to. Put the attribute back on the '
        . '`<phpunit>` element, or fix what raises the diagnostic (G11).',
        implode("\n  ", $missing),
    ));
});

it('G11 — a suppressed diagnostic is still reported', function (): void {
    // One attribute per kind, because PHPUnit has one per kind and a missing
    // one is silent about exactly the kind it names.
    $missing = notTurnedOn(settingsDeclaredAt('/phpunit/source'), [
        'ignoreSuppressionOfDeprecations',
        'ignoreSuppressionOfPhpDeprecations',
        'ignoreSuppressionOfErrors',
        'ignoreSuppressionOfNotices',
        'ignoreSuppressionOfPhpNotices',
        'ignoreSuppressionOfWarnings',
        'ignoreSuppressionOfPhpWarnings',
    ]);

    expect($missing)->toBe([], sprintf(
        "phpunit.xml lets a suppressed diagnostic out of the result:\n  %s\n\n"
        . 'A diagnostic raised inside `@` is dropped by PHPUnit\'s issue filter before '
        . '`failOnWarning` and its neighbours ever see it, so the run prints one and '
        . 'exits zero — the summary and the exit code disagree and only one of them is '
        . 'a gate. A framework raises most of what it raises at boot under `@`, so this '
        . 'is the kind this repository actually meets. Put the attribute back on '
        . '`<source>` (G11).',
        implode("\n  ", $missing),
    ));
});

it('G11 — a diagnostic our code provoked inside a dependency still counts', function (): void {
    $declared = settingsDeclaredAt('/phpunit/source');
    $narrowed = [];

    // Both default to false, so this refuses a setting rather than requiring
    // one. Turning either on keeps only what was raised inside `<source>`,
    // which is the wrong half: a missing file read by `vlucas/phpdotenv` is
    // reported against the dependency and caused by this repository.
    foreach (['restrictNotices', 'restrictWarnings'] as $setting) {
        if (($declared[$setting] ?? 'false') === 'true') {
            $narrowed[] = $setting;
        }
    }

    expect($narrowed)->toBe([], sprintf(
        "phpunit.xml only counts a diagnostic raised in our own files:\n  %s\n\n"
        . 'Our code calling a dependency badly is reported at the line inside the '
        . 'dependency, so this hides the whole class of diagnostic a test run is most '
        . 'likely to produce while reading as a stricter setting than the default '
        . '(G11).',
        implode(', ', $narrowed),
    ));
});
