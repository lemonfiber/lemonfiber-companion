<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhereTheDataGoes;
use Tests\Support\WhatAScopeSays;

it('carries what was restored, what wrote the copy and where the data went', function (): void {
    $done = ACopyPutBack::reported(ScopeOfACopy::oneService(ServiceId::called('sonarr')), '0.9.0', WhereTheDataGoes::whereItWas());

    expect(WhatAScopeSays::of($done->scope()))->toBe('service:sonarr')
        ->and($done->takenBy())->toBe('0.9.0')
        ->and($done->whereTheDataWent()->isElsewhere())->toBeFalse();
});

it('refuses a report that will not say what wrote the copy', function (): void {
    expect(fn(): ACopyPutBack => ACopyPutBack::reported(ScopeOfACopy::theWholeStack(), '  ', WhereTheDataGoes::whereItWas()))
        ->toThrow(KeepingSaysNothing::class, '`from_version`');
});
