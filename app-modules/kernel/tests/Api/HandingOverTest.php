<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HandingOver;

it('asks the stack by lemonfiber\'s own name for each act', function (): void {
    // The wire's words rather than this app's. A name the stack does not offer
    // is refused by name, which would be the operator's yes answered with a
    // refusal about spelling.
    expect(HandingOver::Install->asked())->toBe('hosting-install')
        ->and(HandingOver::Remove->asked())->toBe('hosting-remove');
});

it('builds the question and what the act means from the case', function (): void {
    expect(HandingOver::Install->askedOnTheScreen())->toBe('stacks.handing_over_asked.install')
        ->and(HandingOver::Remove->askedOnTheScreen())->toBe('stacks.handing_over_asked.remove')
        ->and(HandingOver::Install->meansOnTheScreen())->toBe('stacks.handing_over_means.install')
        ->and(HandingOver::Remove->meansOnTheScreen())->toBe('stacks.handing_over_means.remove');
});
