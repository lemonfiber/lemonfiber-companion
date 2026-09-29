<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Unsealing;

use function sprintf;

/** Which arm an opening answered on, and what it carried, as one word. */
function whatOpeningSaid(Unsealing $opening): string
{
    return $opening->either(
        opened: static fn(Unsealed $value): Code => Code::of(sprintf('opened:%s', $value->inTheClear())),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();
}

it('hands the opened arm the value that was sealed', function (): void {
    expect(whatOpeningSaid(Unsealing::opened(Unsealed::of('what-was-kept'))))->toBe('opened:what-was-kept');
});

it('answers unreadable with nothing to hand over', function (): void {
    expect(whatOpeningSaid(Unsealing::unreadable()))->toBe('unreadable');
});

it('opens a value that was empty as the empty value, not as unreadable', function (): void {
    // An empty value is still a value somebody sealed. Reading absence off
    // the string rather than off the arm would lose it.
    expect(Unsealing::opened(Unsealed::of(''))->either(
        opened: static fn(Unsealed $value): Code => Code::of(sprintf('opened:[%s]', $value->inTheClear())),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown())->toBe('opened:[]');
});
