<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Operator\Internal\Presenters\HowWithheldNotesRead;

it('says why the notes are withheld, and what that means', function (HowTheNotesStand $notes, string $said, string $means): void {
    $withheld = new HowWithheldNotesRead()->of($notes);

    expect($withheld->said)->toBe($said)
        ->and($withheld->meansSaid)->toBe($means);
})->with([
    'not written yet' => [HowTheNotesStand::Pending, 'stacks.versions.notes_pending', 'stacks.versions.notes_pending_means'],
    'out of step' => [HowTheNotesStand::Stale, 'stacks.versions.notes_stale', 'stacks.versions.notes_stale_means'],
]);

it('withholds nothing where the notes describe the running build', function (): void {
    $withheld = new HowWithheldNotesRead()->of(HowTheNotesStand::Current);

    expect($withheld->said)->toBe('')
        ->and($withheld->meansSaid)->toBe('');
});
