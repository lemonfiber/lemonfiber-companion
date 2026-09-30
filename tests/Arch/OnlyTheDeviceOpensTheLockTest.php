<?php

declare(strict_types=1);

use Modules\Device\Api\PlatformAuth;
use Tests\Support\OurCode;

// An open lock is made in one line of one file.
//
// `Lock::openedBy()` takes an `Authenticated`, and `Authenticated::byTheDevice()`
// is its only maker. That makes "open, but nobody checked" a line somebody has
// to write, and this is what keeps that line in the adapter that reads the
// device's own answer. A second caller anywhere in what ships would be a second
// way to open the lock.

/**
 * Whether a file's code, comments aside, calls the maker of an open lock.
 *
 * Read as tokens, because the docblocks around the lock name the maker in
 * prose, and a mention is not a call.
 */
function callsTheMakerOfAnOpenLock(string $file): bool
{
    $code = '';

    foreach (PhpToken::tokenize((string) file_get_contents($file)) as $token) {
        if (! $token->is([T_COMMENT, T_DOC_COMMENT, T_WHITESPACE])) {
            $code .= $token->text;
        }
    }

    return str_contains($code, 'Authenticated::byTheDevice(');
}

it('names the maker of an open lock only where the device answers', function (): void {
    $callers = array_values(array_filter(OurCode::sourceFiles(), callsTheMakerOfAnOpenLock(...)));

    expect($callers)->toBe([(string) new ReflectionClass(PlatformAuth::class)->getFileName()]);
});
