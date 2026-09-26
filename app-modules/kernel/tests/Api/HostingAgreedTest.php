<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\HandoverSaysNothing;
use Modules\Kernel\Api\HostingAgreed;

it('carries the act that was agreed to and the command it is about', function (): void {
    foreach (HandingOver::cases() as $doing) {
        $agreed = HostingAgreed::to($doing, 'watch');

        expect($agreed->doing())->toBe($doing)
            ->and($agreed->named())->toBe('watch');
    }
});

it('asks by the name without the space around it', function (): void {
    expect(HostingAgreed::to(HandingOver::Install, "  boot\n")->named())->toBe('boot');
});

it('refuses to be about no command at all', function (): void {
    // An install of nothing would reach the stack after the operator had said
    // yes to it, and come back refused by name.
    expect(static fn(): HostingAgreed => HostingAgreed::to(HandingOver::Remove, '   '))
        ->toThrow(HandoverSaysNothing::class, 'which command');
});
