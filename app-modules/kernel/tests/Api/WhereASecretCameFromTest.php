<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function array_unique;
use function count;
use function expect;
use function it;

use Modules\Kernel\Api\WhereASecretCameFrom;

it('says each place a secret came from with a line of its own', function (): void {
    $said = array_map(static fn(WhereASecretCameFrom $from): string => $from->saidOnTheScreen(), WhereASecretCameFrom::cases());

    expect(count(array_unique($said)))->toBe(count(WhereASecretCameFrom::cases()))
        ->and($said)->each->toStartWith('plugins.settings.secret_from.');
});
