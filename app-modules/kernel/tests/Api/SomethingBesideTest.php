<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\SomethingBeside;

it('keeps what it is and whose it is', function (): void {
    $beside = SomethingBeside::named('/srv/media', 'Your library');

    expect([$beside->what(), $beside->why()])->toBe(['/srv/media', 'Your library']);
});

it('refuses either word blank, naming the one', function (): void {
    expect(fn(): SomethingBeside => SomethingBeside::named('', 'Your library'))->toThrow(KeepingSaysNothing::class, '`what`')
        ->and(fn(): SomethingBeside => SomethingBeside::named('/srv/media', ' '))->toThrow(KeepingSaysNothing::class, '`why`');
});
