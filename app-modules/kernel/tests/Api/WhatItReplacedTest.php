<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnOriginIsUnnamed;
use Modules\Kernel\Api\WhatItReplaced;
use Modules\Kernel\Api\WhoPutItThere;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which case a replaced value is, and what it holds. */
function theWordForWhatWasReplaced(WhatItReplaced $replaced): string
{
    return $replaced->whichever(
        held: static fn(string $value): TheWordCarriedOut => new TheWordCarriedOut(sprintf('held:%s', $value)),
        nothingSet: static fn(): TheWordCarriedOut => new TheWordCarriedOut('nothing'),
        withheld: static fn(): TheWordCarriedOut => new TheWordCarriedOut('withheld'),
    )->said;
}

it('keeps what it held, that nothing was set, or that a credential is withheld, and where it came from', function (): void {
    $from = WhoPutItThere::operator();

    expect(theWordForWhatWasReplaced(WhatItReplaced::held('8096', $from)))->toBe('held:8096')
        ->and(theWordForWhatWasReplaced(WhatItReplaced::nothingSet($from)))->toBe('nothing')
        ->and(theWordForWhatWasReplaced(WhatItReplaced::withheld($from)))->toBe('withheld')
        ->and(WhatItReplaced::withheld($from)->from())->toBe($from);
});

it('refuses a held value that is blank', function (): void {
    expect(fn(): WhatItReplaced => WhatItReplaced::held(' ', WhoPutItThere::bundled()))->toThrow(AnOriginIsUnnamed::class, 'blank value');
});
