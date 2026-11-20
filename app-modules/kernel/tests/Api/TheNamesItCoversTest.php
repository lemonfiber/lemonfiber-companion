<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACertificateName;
use Modules\Kernel\Api\CertificateSaysTooMuch;
use Modules\Kernel\Api\TheNamesItCovers;

it('keeps the names in the stack\'s order, and how many more it left out', function (): void {
    $names = TheNamesItCovers::these(3, ACertificateName::said('loft.home.arpa', cut: false), ACertificateName::said('192.168.1.42', cut: false));
    $said = [];

    foreach ($names as $name) {
        $said[] = $name->text();
    }

    expect($said)->toBe(['loft.home.arpa', '192.168.1.42'])
        ->and($names->more())->toBe(3)
        ->and(TheNamesItCovers::these(0)->more())->toBe(0);
});

it('refuses fewer than none left out', function (): void {
    expect(static fn(): TheNamesItCovers => TheNamesItCovers::these(-1))
        ->toThrow(CertificateSaysTooMuch::class, '`names_more` at -1');
});
