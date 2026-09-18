<?php

declare(strict_types=1);

use Tests\Support\Tree;

// One list of the ways to open a connection, and both gates read it.
//
// The requirement is that every call to the stack goes through the SDK, and it
// was enforced in two places with two disjoint lists. `phpstan.neon` refused
// `curl_setopt`, `curl_setopt_array` and `stream_context_create`;
// `ModuleBoundariesTest` refused `curl_init`, `curl_exec`, `fsockopen` and
// `stream_socket_client`. Neither file mentioned the other.
//
// That is one fact written twice with no overlap, which is the worst version of
// it: there was no single place to look, so somebody adding `socket_create` or
// `curl_multi_init` would find one list, add to it, and leave the other gate
// unable to see the function it was written for. Both gates would still be
// green and the requirement would be half-held.
//
// The two mechanisms have to stay two, because they read different things —
// the analyser reads a call's arguments, which is how it catches verification
// being switched off in a positional option, and the arch rule reads usage
// anywhere in a module. What is unified here is the vocabulary: this list is
// the one place the ways are named, and the test below says which of them no
// gate holds.

/**
 * Every way to open a connection without the SDK.
 *
 * `curl_setopt` and `stream_context_create` are on the list even though they
 * open nothing on their own. They are how verification gets turned off — both
 * take their switch positionally, where no array rule can read it — so they are
 * part of the same vocabulary and the analyser is the gate that holds them.
 */
const BY_HAND = [
    'curl_init', 'curl_exec', 'curl_setopt', 'curl_setopt_array', 'curl_multi_init',
    'fsockopen', 'pfsockopen', 'stream_socket_client', 'stream_context_create',
    'socket_create', 'socket_connect',
];

it('N1-R16 — every way to open a connection is held by a gate', function (): void {
    $analyser = (string) file_get_contents(Tree::at('phpstan.neon'));
    $arch = (string) file_get_contents(Tree::at('tests/Arch/ModuleBoundariesTest.php'));

    $unheld = [];

    foreach (BY_HAND as $way) {
        $inAnalyser = str_contains($analyser, sprintf("'%s()'", $way));
        $inArch = str_contains($arch, sprintf("'%s'", $way));

        if (! $inAnalyser && ! $inArch) {
            $unheld[] = $way;
        }
    }

    expect($unheld)->toBe([], sprintf(
        "These ways to open a connection are on the list and no gate refuses them:\n  %s\n\n"
        . 'Add each to whichever gate can see it: `phpstan.neon` reads a call and its '
        . 'arguments, which is what catches verification being switched off in a '
        . 'positional option; the arch rule in ModuleBoundariesTest reads usage anywhere '
        . "in a module.\nThe two mechanisms stay two because they read different things. "
        . 'The list is what stops them drifting apart — it was written twice, with no '
        . 'overlap, so there was no single place to look and adding to one left the '
        . 'other blind to the function it was written for (N1-R16, N1-R21).',
        implode("\n  ", $unheld),
    ));
});
