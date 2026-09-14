<?php

declare(strict_types=1);

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatToDoWithIt;

it('N2-R8 — carries the verb that was agreed to, whichever it was', function (): void {
    foreach (WhatToDoWithIt::cases() as $doing) {
        expect(AgreedTo::theService($doing, ServiceId::called('sonarr'))->doing())->toBe($doing);
    }
});

it('N2-R7 — names one service, and says it is not a form', function (): void {
    $agreed = AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr'));

    expect($agreed->named())->toBe('sonarr');
    expect($agreed->isAboutAForm())->toBeFalse();
});

it('N2-R7 — names a whole form, and says that is what it is', function (): void {
    // The two travel under different arguments on the wire, so this is the one
    // thing an adapter has to ask: a form's name sent as a service's would stop
    // nothing and report that it had.
    $agreed = AgreedTo::theForm(WhatToDoWithIt::Restart, Form::called('downloads'));

    expect($agreed->named())->toBe('downloads');
    expect($agreed->isAboutAForm())->toBeTrue();
});

it('reads the name the same way whichever it was agreed about', function (): void {
    // What the union buys. Both arms answer `named()`, so no reader carries a
    // branch, and there is no third state for one to have to describe.
    expect(AgreedTo::theService(WhatToDoWithIt::Start, ServiceId::called('same'))->named())
        ->toBe(AgreedTo::theForm(WhatToDoWithIt::Start, Form::called('same'))->named());
});
