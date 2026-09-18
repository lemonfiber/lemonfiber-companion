<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;

use Modules\Kernel\Api\Scheme;

it('answers N1-R12 for each way a stack is dialled', function (): void {
    expect(Scheme::Https->isEncrypted())->toBeTrue();
    expect(Scheme::Http->isEncrypted())->toBeFalse();
});

it('names every way a stack is dialled, and no others', function (): void {
    // Not a restatement of the enum for its own sake. `Address` refuses a scheme
    // this does not name, so a case added here quietly widens what pairing
    // material may carry — and `Scheme::Ftp` would be a one-word change with no
    // other test in the repository to notice it.
    //
    // The list is written out because this is the one place where writing it out
    // is the assertion. Everywhere else it is read.
    expect(Scheme::cases())->toBe([Scheme::Http, Scheme::Https]);
});

it('is a decision each case answers for itself', function (): void {
    // A case added without a thought about privacy inherits whichever answer the
    // implementation happens to give it. This will not say which answer is
    // right — nothing can — but it does say that both answers are in use, so a
    // vocabulary that had drifted to one of them fails here.
    $encrypted = [];

    foreach (Scheme::cases() as $scheme) {
        $encrypted[$scheme->isEncrypted() ? 'yes' : 'no'][] = $scheme;
    }

    expect(count($encrypted))->toBe(2);
});
