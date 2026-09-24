<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhereThisCopyStands;

use function sprintf;

it('names a catalogue key for each case, from its wire word', function (WhereThisCopyStands $case): void {
    expect($case->saidOnTheScreen())->toBe(sprintf('stacks.itself.standing.%s', $case->value));
})->with(WhereThisCopyStands::cases());
