<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\KeepingSaysNothing;
use Modules\Kernel\Api\WhereThingsAreKept;

it('keeps the directory and what is under it', function (): void {
    $root = WhereThingsAreKept::at('/srv/lemonfiber', 'Everything the stack writes');

    expect([$root->where(), $root->what()])->toBe(['/srv/lemonfiber', 'Everything the stack writes']);
});

it('refuses either word blank, naming the one', function (): void {
    expect(fn(): WhereThingsAreKept => WhereThingsAreKept::at('', 'Everything'))->toThrow(KeepingSaysNothing::class, '`at`')
        ->and(fn(): WhereThingsAreKept => WhereThingsAreKept::at('/srv/lemonfiber', ' '))->toThrow(KeepingSaysNothing::class, '`what`');
});
