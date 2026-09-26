<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnExistingSetup;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\WhatACopyHolds;

it('names the project and the trees it was read from, as given', function (): void {
    $setup = AnExistingSetup::of(' media ', WhatACopyHolds::these('/srv/arr/config'));

    expect($setup->project())->toBe(' media ')
        ->and(iterator_to_array($setup->trees(), preserve_keys: false))->toBe(['/srv/arr/config']);
});

it('refuses a project nobody named', function (): void {
    expect(fn(): AnExistingSetup => AnExistingSetup::of('  ', WhatACopyHolds::these()))->toThrow(KeepingSaysNothing::class, '`project`');
});
