<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhetherItIsOffered;

it('lets a button be pressed where the stack has it, set up or not, and where it could not be asked', function (): void {
    // One not set up is offered and says what is missing; not knowing keeps
    // the button, and the request reports why it could not be asked.
    expect(WhetherItIsOffered::Offered->offersAnAction())->toBeTrue()
        ->and(WhetherItIsOffered::NotSetUp->offersAnAction())->toBeTrue()
        ->and(WhetherItIsOffered::NotKnown->offersAnAction())->toBeTrue();
});

it('draws and explains a button the account may not use and one the stack is too old for, and presses neither', function (): void {
    expect(WhetherItIsOffered::NotTheirs->offersAnAction())->toBeFalse()
        ->and(WhetherItIsOffered::NeedsANewerLemonfiber->offersAnAction())->toBeFalse();
});

it('names an update as what provides only what the stack is too old for', function (): void {
    $provided = [];

    foreach (WhetherItIsOffered::cases() as $case) {
        if ($case->isProvidedByAnUpdate()) {
            $provided[] = $case;
        }
    }

    expect($provided)->toBe([WhetherItIsOffered::NeedsANewerLemonfiber]);
});

it('is the five answers a button can give', function (): void {
    expect(WhetherItIsOffered::cases())->toBe([
        WhetherItIsOffered::Offered,
        WhetherItIsOffered::NotSetUp,
        WhetherItIsOffered::NotTheirs,
        WhetherItIsOffered::NeedsANewerLemonfiber,
        WhetherItIsOffered::NotKnown,
    ]);
});
