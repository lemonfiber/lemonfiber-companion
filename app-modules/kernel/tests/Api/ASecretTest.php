<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhoseASettingIs;

it('holds a secret under its name with the spaces around it left off', function (): void {
    expect(ASecret::read(' vpn.key ', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), held: true, from: WhereASecretCameFrom::Operator)->name())->toBe('vpn.key');
});
