<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\ASettingToReveal;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\WhatFilenamesShow;
use Tests\Support\WhatABundleSays;

/** A bundle described with every choice away from its default. */
function aBundleDescribedWithEveryChoice(): ABundleAsked
{
    return ABundleAsked::described(
        HowManyLines::of(50),
        WhatFilenamesShow::Shown,
        SettingsToReveal::none()->with(ASettingToReveal::named('SONARR_URL')),
    );
}

it('describes a bundle without writing it, carrying every choice', function (): void {
    $asked = aBundleDescribedWithEveryChoice();

    expect($asked->writes())->toBeFalse()
        ->and($asked->lines()->figure())->toBe(50)
        ->and($asked->filenames())->toBe(WhatFilenamesShow::Shown)
        ->and(WhatABundleSays::namesOf($asked->revealing()))->toBe(['SONARR_URL'])
        ->and($asked->asked())->toBe('support');
});

it('writes the bundle described, changing nothing else', function (): void {
    $written = aBundleDescribedWithEveryChoice()->written();

    expect($written->writes())->toBeTrue()
        ->and($written->lines()->figure())->toBe(50)
        ->and($written->filenames())->toBe(WhatFilenamesShow::Shown)
        ->and(WhatABundleSays::namesOf($written->revealing()))->toBe(['SONARR_URL'])
        ->and($written->asked())->toBe('support');
});
