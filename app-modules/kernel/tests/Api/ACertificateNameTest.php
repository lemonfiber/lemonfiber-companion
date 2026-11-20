<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACertificateName;
use Modules\Kernel\Api\CertificateSaysTooMuch;

it('keeps a name exactly as the stack wrote it out, markup and written-out escapes included, and whether it was cut', function (): void {
    $name = ACertificateName::said('<i>CN=loft</i>\x1b[31m', cut: true);

    expect($name->text())->toBe('<i>CN=loft</i>\x1b[31m')
        ->and($name->isCut())->toBeTrue()
        ->and(ACertificateName::said('CN=Zoë', cut: false)->isCut())->toBeFalse();
});

it('refuses a name still holding a control character the stack promises to write out', function (string $text): void {
    expect(static fn(): ACertificateName => ACertificateName::said($text, cut: false))->toThrow(CertificateSaysTooMuch::class);
})->with([
    'an escape' => ["CN=loft\e[31m"],
    'a line break' => ["CN=loft\nO=evil"],
    'a nul' => ["CN=loft\0"],
    'a delete' => ["CN=loft\x7F"],
    'a C1 control' => ["CN=\u{0085}loft"],
]);
