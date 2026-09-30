<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Internal;

use function expect;
use function it;

use Modules\Vault\Internal\KeptUnder;

it('names a record beneath its key, a dot before each name', function (): void {
    expect(KeptUnder::WorkLeftRunning->beneath('repair', 'a1b2'))->toBe('lemonfiber.left-running.repair.a1b2')
        ->and(KeptUnder::Session->beneath('a1b2'))->toBe('lemonfiber.session.a1b2');
});
