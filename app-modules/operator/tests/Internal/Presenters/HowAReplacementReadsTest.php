<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\ACertificateReplacement;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\TheCredentialGrants;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatAReplacementCameTo;
use Modules\Kernel\Api\WhatTheStoreProtects;
use Modules\Kernel\Api\WhereAReplacementSettled;
use Modules\Operator\Internal\Presenters\HowAReplacementReads;

/** How a replacement is toned once the stack answered. */
function theToneOf(ACertificateReplacement $replacement): string
{
    $held = TheCredentialsHeld::of(WhatTheStoreProtects::said('Plain files', Remarks::of(), Remarks::of()), TheCredentialGrants::of());

    return new HowAReplacementReads()->answered(WhatAReplacementCameTo::of($replacement, $held))->replacement?->tone ?? '';
}

it('tones a reading as wanting a look, a landed replacement as fine, and anything else as not known to have landed', function (): void {
    expect(theToneOf(ACertificateReplacement::read('an-offer', 'the decline service is handed it')))->toBe(Tone::Attention->value)
        ->and(theToneOf(ACertificateReplacement::settled(WhereAReplacementSettled::Replaced, 'it serves the new one')))->toBe(Tone::Fine->value)
        ->and(theToneOf(ACertificateReplacement::settled(WhereAReplacementSettled::ReplacedUnproven, 'not proven')))->toBe(Tone::Unknown->value)
        ->and(theToneOf(ACertificateReplacement::read('', 'no offer to agree to')))->toBe(Tone::Unknown->value);
});
