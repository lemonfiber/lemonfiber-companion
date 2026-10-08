<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\WhatAProofCameTo;
use Modules\Kernel\Api\WhatAProofSays;

it('tells not asked from passed, and could not conclude from failed, and only failed stops an install', function (): void {
    $answers = [
        'not asked' => [WhatAProofCameTo::notAsked(), WhatAProofSays::NotAsked, [], false],
        'passed' => [WhatAProofCameTo::passed(), WhatAProofSays::Passed, [], false],
        'failed' => [WhatAProofCameTo::failed(PluginLines::under('faults', 'It answered 502')), WhatAProofSays::Failed, ['It answered 502'], true],
        'unproven' => [WhatAProofCameTo::unproven(' The service did not settle '), WhatAProofSays::Unproven, ['The service did not settle'], false],
        'failing as declared' => [WhatAProofCameTo::failingAsDeclared(PluginLines::under('reason', 'Recorded against an old release')), WhatAProofSays::FailingAsDeclared, ['Recorded against an old release'], false],
    ];

    foreach ($answers as $which => [$cameTo, $says, $said, $stops]) {
        expect($cameTo->says())->toBe($says, $which)
            ->and(iterator_to_array($cameTo->said(), preserve_keys: false))->toBe($said, $which)
            ->and($cameTo->stopsAnInstall())->toBe($stops, $which);
    }

    expect(WhatAProofSays::FailingAsDeclared->saidOnTheScreen())->toBe('plugins.proof.failing_as_declared');
});
