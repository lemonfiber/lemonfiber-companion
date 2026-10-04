<?php

declare(strict_types=1);

// What an operator can do at the machine instead of here, said as plainly as
// anything else on the screen.
//
// A route offered under a caution that discourages taking it has been closed
// politely. The lines below are where the app hands the operator a way around
// it: updating lemonfiber by hand, setting up what survives a restart, and what
// handing a command to the machine's service manager does. Each is read in
// every language this app speaks and held to having none of the phrases a
// discouragement is made of.

/** The catalogue lines that offer a way around the app. */
const LINES_THAT_OFFER_A_WAY_AROUND_THE_APP = [
    'stacks.itself.run_at_the_machine',
    'stacks.itself.owner',
    'stacks.itself.standing.managed-externally',
    'stacks.keeps_nothing_running_action',
    'stacks.keeps-running.unsupported',
    'stacks.handing_over.install',
    'stacks.handing_over.remove',
    'stacks.handing_over_means.install',
    'stacks.handing_over_means.remove',
    'stacks.guard.apart',
    'stacks.guard.to_host_one',
    'stacks.command.ran',
    'stacks.command.will_run',
];

/** What a discouragement is made of, in each language, lower case. */
const WHAT_A_DISCOURAGEMENT_SAYS = [
    'en' => ['careful', 'caution', 'at your own risk', 'not recommended', 'advanced', 'are you sure', 'warning'],
    'nl' => ['voorzichtig', 'op eigen risico', 'afgeraden', 'niet aanbevolen', 'geavanceerd', 'weet je het zeker', 'waarschuwing'],
];

it('offers every way around the app without a word that discourages taking it', function (string $locale): void {
    app()->setLocale($locale);
    $discouraging = [];

    foreach (LINES_THAT_OFFER_A_WAY_AROUND_THE_APP as $key) {
        $said = __($key);

        if (! is_string($said) || $said === $key) {
            $discouraging[] = sprintf('%s has no line', $key);

            continue;
        }

        foreach (WHAT_A_DISCOURAGEMENT_SAYS[$locale] as $phrase) {
            if (str_contains(mb_strtolower($said), $phrase)) {
                $discouraging[] = sprintf('%s says "%s"', $key, $phrase);
            }
        }
    }

    expect($discouraging)->toBe([]);
})->with(['en', 'nl']);
