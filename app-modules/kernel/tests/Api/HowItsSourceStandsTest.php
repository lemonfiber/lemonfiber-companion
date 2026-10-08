<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItsSourceStands;
use Modules\Kernel\Api\WhereItsSourceStands;

it('says how a source stands with the stack\'s reason, and nothing where the stack said nothing of it', function (): void {
    expect(HowItsSourceStands::reachable()->standing())->toBe(WhereItsSourceStands::Reachable)
        ->and(HowItsSourceStands::reachable()->why())->toBe('')
        ->and(HowItsSourceStands::unasked(' Set not to ask ')->why())->toBe('Set not to ask')
        ->and(HowItsSourceStands::unasked('Set not to ask')->standing()->saidOnTheScreen())->toBe('plugins.source.unasked')
        ->and(HowItsSourceStands::notSaid()->standing()->saidOnTheScreen())->toBe('');
});
