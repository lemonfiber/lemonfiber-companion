<?php

declare(strict_types=1);

use Tests\Support\Tree;

/**
 * `sdk-bump` must read the open pull request before it resets the branch.
 *
 * **The ordering is the whole of the fix, and nothing else would notice it
 * moving back.** Resetting the branch closes the pull request open on it, and
 * GitHub does that asynchronously — so a read taken afterwards is still
 * answered with the pull request that is on its way to closed. The workflow
 * then reports it as one it can update, opens nothing, and leaves the commit on
 * a branch whose pull request is shut.
 *
 * That is not hypothetical. It happened twice on 2026-09-22, at 17:32 and at
 * 17:56. `sdk-drift` is a required check here, so each time it was every pull
 * request in this repository red with nothing saying why, until somebody read a
 * closed pull request's timeline to find out.
 *
 * A test over the text rather than over a run, because the failure is an
 * ordering and a run that exercised it would need a forge that closes things
 * slowly. What is held is the one thing that must not swap back: the number is
 * taken while the answer cannot be wrong.
 */
it('takes the pull request number before it moves the branch', function (): void {
    $workflow = (string) file_get_contents(Tree::at('.github/workflows/sdk-bump.yml'));

    // Split at the reset rather than compared as offsets. `strpos` answers
    // `false` for a needle that is gone, and `false` goes on to compare as
    // offset zero — the earliest position there is, so a guard written that way
    // passes loudest exactly when the thing it names has been deleted.
    $aroundTheReset = explode('force=true', $workflow, 2);

    expect(count($aroundTheReset))->toBe(
        2,
        'sdk-bump no longer force-resets its branch, so this guard is measuring '
        . 'an ordering that no longer exists. Read the workflow before deleting '
        . 'this — the hazard it names may have moved rather than gone.',
    );

    expect(str_contains($aroundTheReset[0], 'carried="$(gh pr list'))->toBeTrue(
        "sdk-bump does not read the open pull request before it resets the branch.\n\n"
        . 'Resetting closes the pull request open on the branch, and that close '
        . 'is asynchronous: a read afterwards can still be answered with the one '
        . 'on its way to closed. The run then reports it as a pull request it '
        . "can update, opens nothing, and the bump sits on a branch nothing "
        . "points at — which is how #298 and #300 were both lost.\n"
        . 'Take the number first, while the answer cannot be wrong.',
    );
});

/**
 * And having taken it, the workflow must act on it.
 *
 * A number read and then unused is the same outcome by a longer route. The
 * reopen is what turns the carried number back into an open pull request, and
 * it is the half a tidy-up would remove first, because on a run where nothing
 * was closed it does nothing at all.
 */
it('reopens the pull request its own reset closed', function (): void {
    $workflow = (string) file_get_contents(Tree::at('.github/workflows/sdk-bump.yml'));

    // `toContain` takes needles rather than a message, so the reason goes on an
    // assertion that has somewhere to put it. A guard whose failure says only
    // *false is not true* is one whose next reader re-derives the incident.
    expect(str_contains($workflow, 'gh pr reopen'))->toBeTrue(
        'sdk-bump carries the pull request number past the reset and never '
        . 'reopens it, so the reset still ends with a closed pull request and a '
        . 'branch nothing points at.',
    );
});
