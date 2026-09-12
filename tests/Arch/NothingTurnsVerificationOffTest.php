<?php

declare(strict_types=1);

use Tests\Support\Tree;

// N1-R21 — one vocabulary for certificate verification, read by both gates.
//
// Two gates hold this requirement and they read different things.
// `NoWeakenedTlsRule` reads a call's options and knows each spelling's
// *polarity* — `verify => false` turns it off, `insecure => true` turns it off
// — because it looks at the value. `Settings::ABOUT_VERIFICATION` reads
// configuration key names as text, where there is no value to look at and
// substring matching is the point.
//
// They are not the same mechanism and should not become one. What they share is
// a vocabulary, and that had drifted: the analyser did not know `verify_ssl`,
// `verify_tls`, `verify_cert`, `ssl_verify` or `tls_verify`, every one of which
// is an option key a real client accepts. A line switching verification off
// under any of those names passed the analyser, and neither file named the
// other, so nothing suggested looking.
//
// This is the one place the spellings are written down. Neither gate is asked
// to hold all of them — an option key is not a config key — but every spelling
// must be held by at least one, and the failure says which are held by neither.

/** Every spelling that means "certificate verification", in either half. */
const MEANS_VERIFICATION = [
    'verify', 'verify_peer', 'verify_peer_name', 'verify_host',
    'verify_ssl', 'verify_tls', 'verify_cert', 'ssl_verify', 'tls_verify',
    'curlopt_ssl_verifypeer', 'curlopt_ssl_verifyhost', 'ssl_verifypeer', 'ssl_verifyhost',
    'allow_self_signed', 'verify_expiry', 'insecure', 'skip_verify', 'no_verify',
];

it('N1-R21 — every spelling of verification is held by a gate', function (): void {
    $analyser = (string) file_get_contents(Tree::at('phpstan/Rules/NoWeakenedTlsRule.php'));
    $settings = (string) file_get_contents(Tree::at('tests/Support/Settings.php'));

    $unheld = [];

    foreach (MEANS_VERIFICATION as $spelling) {
        $said = sprintf("'%s'", $spelling);

        if (! str_contains($analyser, $said) && ! str_contains($settings, $said)) {
            $unheld[] = $spelling;
        }
    }

    expect($unheld)->toBe([], sprintf(
        "These spellings mean certificate verification and no gate knows them:\n  %s\n\n"
        . 'Add each to whichever gate can see it. `NoWeakenedTlsRule` reads a call and '
        . 'its options, so it needs the polarity too — off when false, or off when true. '
        . '`Settings::ABOUT_VERIFICATION` reads configuration key names as text and '
        . "matches on substrings.\nThe two stay two because they read different things. "
        . 'The vocabulary is what stops them drifting, and it had: five spellings a real '
        . 'client accepts were unknown to the analyser, so a line switching verification '
        . 'off under any of them passed (N1-R21).',
        implode("\n  ", $unheld),
    ));
});
