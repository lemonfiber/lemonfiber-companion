<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use ArrayObject;

use function expect;
use function it;

use Modules\Kernel\Api\WhoseASettingIs;

it('holds a namespace by its name without the space around it, and the core\'s by none', function (): void {
    /** @return ArrayObject<int, string> */
    $said = static fn(WhoseASettingIs $whose): ArrayObject => $whose->either(
        static fn(): ArrayObject => new ArrayObject(['the core']),
        static fn(string $namespace): ArrayObject => new ArrayObject([$namespace]),
    );

    expect($said(WhoseASettingIs::theNamespaces('  acme/plugin  '))->getArrayCopy())->toBe(['acme/plugin'])
        ->and($said(WhoseASettingIs::theCores())->getArrayCopy())->toBe(['the core']);
});
