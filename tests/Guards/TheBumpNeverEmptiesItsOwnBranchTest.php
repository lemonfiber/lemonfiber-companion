<?php

declare(strict_types=1);

use Tests\Support\Tree;

/**
 * `sdk-bump` must build on a scratch ref, never on the branch its pull request is on.
 *
 * **GitHub closes a pull request whose branch holds no commits ahead of its
 * base.** Resetting the branch to `main` before writing the new lock does
 * exactly that for a moment, and the moment is enough. It shut `#298` and
 * `#300` on 2026-09-22, and because the run then asked GitHub which pull
 * requests were open before the close had finished propagating, it reported
 * each as one it had updated and opened nothing in its place. `sdk-drift` is a
 * required check here, so each occurrence was every pull request in this
 * repository red for a dependency bump nobody could see was missing.
 *
 * The answer is not to notice the close and undo it. It is not to close at all:
 * build the commit somewhere else and move the branch from yesterday's commit
 * straight to today's, so it is never equal to `main`. `lemonfiber-web` met the
 * same thing first and answered it this way; this holds the companion to the
 * same answer rather than to a second one.
 *
 * A test over the text rather than over a run, because what must not come back
 * is a single API call, and a forge that closes things slowly is not something
 * a suite can stand up.
 */
it('builds the commit on a scratch ref rather than on the pull request branch', function (): void {
    $workflow = (string) file_get_contents(Tree::at('.github/workflows/sdk-bump.yml'));

    expect(str_contains($workflow, '${BRANCH}-staging'))->toBeTrue(
        'sdk-bump no longer builds on a scratch ref. Without one the branch is '
        . 'reset to `main` before the new lock is written, which empties it for '
        . 'a moment and closes the pull request open on it.',
    );
});

/**
 * And the branch itself is never pointed at the base.
 *
 * The scratch ref is only half of it: a run that built elsewhere and then still
 * reset the branch to `main` before moving it across would empty the branch
 * just the same. What may be written to the branch is the commit that was
 * built, and nothing else.
 */
it('never points the pull request branch at the base commit', function (): void {
    $workflow = (string) file_get_contents(Tree::at('.github/workflows/sdk-bump.yml'));

    // Every ref update in the file, paired with the sha it writes. The branch
    // may be moved to `${built}`; only the scratch ref may be moved to `${base}`.
    preg_match_all(
        '/refs\/heads\/\$\{(?<ref>[A-Za-z]+|BRANCH\}-staging)[^"]*"[^\n]*\n[^\n]*sha="\$\{(?<sha>\w+)\}/',
        $workflow,
        $found,
        PREG_SET_ORDER,
    );

    expect($found)->not->toBeEmpty('no ref update was found at all, so this guard read nothing');

    foreach ($found as $one) {
        if ($one['sha'] !== 'base') {
            continue;
        }

        // The scratch ref is written as `${staging}` here and spelled out inline
        // in lemonfiber-web; either way its name says what it is, and the one
        // thing that must never appear on this side is `BRANCH`.
        expect(str_contains($one['ref'], 'staging'))->toBeTrue(sprintf(
            'sdk-bump points `%s` at the base commit. Only the scratch ref may '
            . 'be reset to `main`: pointing the pull request branch there leaves '
            . 'it with nothing ahead of its base, which is what closes the pull '
            . 'request and is how #298 and #300 were lost.',
            $one['ref'],
        ));
    }
});
