<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowLemonfiberWasInstalled;
use Modules\Kernel\Api\HowThisCopyGotThere;
use Modules\Kernel\Api\ItselfSaysNothing;

it('keeps how it was installed and who owns it, or nobody', function (): void {
    $brew = HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Homebrew, 'brew');
    $installer = HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Installer, '');

    expect([$brew->installed(), $brew->owner()])->toBe([HowLemonfiberWasInstalled::Homebrew, 'brew'])
        ->and([$installer->installed(), $installer->owner()])->toBe([HowLemonfiberWasInstalled::Installer, '']);
});

it('refuses a blank owner, which is a name with nothing in it', function (): void {
    expect(fn(): HowThisCopyGotThere => HowThisCopyGotThere::by(HowLemonfiberWasInstalled::Homebrew, '  '))->toThrow(ItselfSaysNothing::class, '`owner`');
});
