<?php

declare(strict_types=1);

use Lemonfiber\Companion\PHPStan\Rules\NoWeakenedTlsRule;
use Tests\Support\Tree;

// One vocabulary for certificate verification, read by both gates.
//
// Three gates hold this requirement and they read different things.
// `NoWeakenedTlsRule` reads a call's options and knows each spelling's
// *polarity* — `verify => false` turns it off, `insecure => true` turns it off
// — because it looks at the value. `Settings::ABOUT_VERIFICATION` reads
// configuration key names as text, where there is no value to look at and
// substring matching is the point. A `disallowedMethodCalls` entry in
// `phpstan.neon` refuses `withoutVerifying()`, which is a *call* and invisible
// to the first two.
//
// This file read two of the three, and said it was "the one place the spellings
// are written down" while `withoutVerifying` appeared in none of them. The
// framework ships that method, it does exactly what `['verify' => false]` does,
// and the only thing standing in front of it was a rule this check could not
// see.
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
//
// Holding a spelling is not the same as holding it correctly, and the second
// rule below is what that cost. `verify_expiry` sat in the analyser's
// *off-when-true* list, where `verify_expiry => true` — asking for the expiry to
// be checked — was refused and `=> false` passed. The rule above saw the
// spelling in the file and called it held.

/** Every spelling that means "certificate verification", in either half. */
const MEANS_VERIFICATION = [
    'verify', 'verify_peer', 'verify_peer_name', 'verify_host',
    'verify_ssl', 'verify_tls', 'verify_cert', 'ssl_verify', 'tls_verify',
    'curlopt_ssl_verifypeer', 'curlopt_ssl_verifyhost', 'ssl_verifypeer', 'ssl_verifyhost',
    'allow_self_signed', 'verify_expiry', 'insecure', 'skip_verify', 'no_verify',
    'withoutVerifying',
];

it('N1-R21 — every spelling of verification is held by a gate', function (): void {
    $analyser = (string) file_get_contents(Tree::at('phpstan/Rules/NoWeakenedTlsRule.php'));
    $settings = (string) file_get_contents(Tree::at('tests/Support/Settings.php'));
    $disallowed = (string) file_get_contents(Tree::at('phpstan.neon'));

    $unheld = [];

    foreach (MEANS_VERIFICATION as $spelling) {
        // Three shapes for three mechanisms, because they hold different
        // things. The first two name an option key, quoted. The third names a
        // method on a class, so the quotes are around the whole identifier and
        // the spelling is what precedes the call.
        $asAKey = sprintf("'%s'", $spelling);
        $asACall = sprintf('::%s(', $spelling);

        if (
            ! str_contains($analyser, $asAKey)
            && ! str_contains($settings, $asAKey)
            && ! str_contains($disallowed, $asACall)
        ) {
            $unheld[] = $spelling;
        }
    }

    expect($unheld)->toBe([], sprintf(
        "These spellings mean certificate verification and no gate knows them:\n  %s\n\n"
        . 'Add each to whichever gate can see it. `NoWeakenedTlsRule` reads a call and '
        . 'its options, so it needs the polarity too — off when false, or off when true. '
        . '`Settings::ABOUT_VERIFICATION` reads configuration key names as text and '
        . 'matches on substrings, and the `disallowedMethodCalls` entry in `phpstan.neon` '
        . "refuses a call.\nThe three stay three because they read different things. "
        . 'The vocabulary is what stops them drifting, and it had: five spellings a real '
        . 'client accepts were unknown to the analyser, so a line switching verification '
        . 'off under any of them passed (N1-R21).',
        implode("\n  ", $unheld),
    ));
});

/** The waivers: a name that asks to be let off rather than to check. */
const WAIVES_VERIFICATION = ['allow_', 'insecure', 'skip_', 'no_'];

/**
 * A spelling's polarity, read off the name.
 *
 * Tokens rather than prose, which is the line this repository draws and has paid
 * for: `verify_peer` and `curlopt_ssl_verifypeer` are identifiers a client
 * defines, not sentences somebody wrote, so reading them is not the mistake the
 * deleted `N1-R17` checker was.
 *
 * The waivers are asked first, because `skip_verify` and `no_verify` contain
 * `verify` and mean the opposite of it.
 */
function polarityOf(string $spelling): string
{
    foreach (WAIVES_VERIFICATION as $waiver) {
        if (str_contains($spelling, $waiver)) {
            return 'off when true';
        }
    }

    return str_contains($spelling, 'verify') ? 'off when false' : 'unreadable';
}

it('N1-R21 — a spelling the analyser knows is in the list its name says', function (): void {
    // A spelling in the wrong list makes the analyser refuse the safe way of
    // writing it and pass the dangerous one — which is worse than not knowing
    // the spelling at all, because the refusal reads as the rule working.
    $lists = new ReflectionClass(NoWeakenedTlsRule::class)->getConstants();

    /** @var list<string> $offWhenFalse */
    $offWhenFalse = $lists['OFF_WHEN_FALSE'];

    /** @var list<string> $offWhenTrue */
    $offWhenTrue = $lists['OFF_WHEN_TRUE'];

    $misplaced = [];

    foreach (['off when false' => $offWhenFalse, 'off when true' => $offWhenTrue] as $held => $spellings) {
        foreach ($spellings as $spelling) {
            $says = polarityOf($spelling);

            if ($says !== $held) {
                $misplaced[] = sprintf('%s is held %s and its name says %s', $spelling, $held, $says);
            }
        }
    }

    sort($misplaced);

    expect($misplaced)->toBe([], sprintf(
        "These are held with the polarity their name contradicts:\n  %s\n\n"
        . 'A name containing `verify` asks for a check and is waived by `false`; one '
        . 'containing `allow_`, `insecure`, `skip_` or `no_` asks to be let off and is '
        . "waived by `true`.\nHeld the wrong way round, the analyser refuses the safe "
        . 'spelling and passes the dangerous one, and the refusal reads as the rule '
        . 'working. A spelling whose name says neither is one this cannot judge — give '
        . 'it a name that reads, or say here why it does not (N1-R21, S3).',
        implode("\n  ", $misplaced),
    ));
});

it('N1-R21 — no spelling is in both polarity lists', function (): void {
    // Both lists is the same fault wearing the safe half as cover: the value
    // would be refused whichever way it was written, so the rule would look
    // stricter than it is and the wrong-polarity half would never be found.
    $lists = new ReflectionClass(NoWeakenedTlsRule::class)->getConstants();

    /** @var list<string> $offWhenFalse */
    $offWhenFalse = $lists['OFF_WHEN_FALSE'];

    /** @var list<string> $offWhenTrue */
    $offWhenTrue = $lists['OFF_WHEN_TRUE'];

    expect(array_values(array_intersect($offWhenFalse, $offWhenTrue)))->toBe([]);
});
