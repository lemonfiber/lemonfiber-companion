<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnOriginIsUnnamed;
use Modules\Kernel\Api\WhatItReplaced;
use Modules\Kernel\Api\WhoPutItThere;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhatWasReplaced
{
    public function __construct(public string $said) {}
}

/** Which case a replaced value is, and what it holds. */
function whatWasReplaced(WhatItReplaced $replaced): string
{
    return $replaced->whichever(
        held: static fn(string $value): WhatWasReplaced => new WhatWasReplaced(sprintf('held:%s', $value)),
        nothingSet: static fn(): WhatWasReplaced => new WhatWasReplaced('nothing'),
        withheld: static fn(): WhatWasReplaced => new WhatWasReplaced('withheld'),
    )->said;
}

it('keeps what it held, that nothing was set, or that a credential is withheld, and where it came from', function (): void {
    $from = WhoPutItThere::operator();

    expect(whatWasReplaced(WhatItReplaced::held('8096', $from)))->toBe('held:8096')
        ->and(whatWasReplaced(WhatItReplaced::nothingSet($from)))->toBe('nothing')
        ->and(whatWasReplaced(WhatItReplaced::withheld($from)))->toBe('withheld')
        ->and(WhatItReplaced::withheld($from)->from())->toBe($from);
});

it('refuses a held value that is blank', function (): void {
    expect(fn(): WhatItReplaced => WhatItReplaced::held(' ', WhoPutItThere::bundled()))->toThrow(AnOriginIsUnnamed::class, 'blank value');
});
