<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatAStopReaches;

it('names a whole form under forms, and services under services, never one as the other', function (): void {
    $form = WhatAStopReaches::theForm(Form::called('downloads'));
    $services = WhatAStopReaches::theServices(ServiceId::called('sabnzbd'), ServiceId::called('qbittorrent'));

    expect(array_map(static fn(Form $one): string => $one->named(), iterator_to_array($form->forms(), preserve_keys: false)))->toBe(['downloads'])
        ->and($form->services()->isEmpty())->toBeTrue()
        ->and($services->forms()->count())->toBe(0)
        ->and(array_map(static fn(ServiceId $one): string => $one->named(), iterator_to_array($services->services(), preserve_keys: false)))->toBe(['sabnzbd', 'qbittorrent']);
});
