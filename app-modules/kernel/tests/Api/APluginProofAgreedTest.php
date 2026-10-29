<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APluginActWasNotRehearsed;
use Modules\Kernel\Api\APluginProofAgreed;
use Tests\Support\APluginAsItArrives;

it('agrees to proving a plugin again against a reading of that, quoting its agreement', function (): void {
    expect(APluginProofAgreed::after(APluginAsItArrives::theProof(), APluginAsItArrives::held())->agreement())->toBe(APluginAsItArrives::AGREEMENT);
});

it('refuses a yes to proving it again against a reading of anything else, or one that names no agreement to quote', function (): void {
    foreach ([APluginAsItArrives::theUpdateReading(), APluginAsItArrives::theProof(agreement: '')] as $reading) {
        expect(static fn(): APluginProofAgreed => APluginProofAgreed::after($reading, APluginAsItArrives::held()))
            ->toThrow(APluginActWasNotRehearsed::class);
    }
});
