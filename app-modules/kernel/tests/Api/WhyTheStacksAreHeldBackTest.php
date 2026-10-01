<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhyTheStacksAreHeldBack;

it('names what happened and what to do about it, from its own stem', function (WhyTheStacksAreHeldBack $why, string $said, string $remedy): void {
    expect($why->said())->toBe($said)
        ->and($why->remedy())->toBe($remedy);
})->with([
    'the store would not open' => [WhyTheStacksAreHeldBack::TheStoreWouldNotOpen, 'connection.store_unreadable', 'connection.store_unreadable_action'],
    'a newer app wrote them' => [WhyTheStacksAreHeldBack::ANewerAppWroteThem, 'connection.store_newer', 'connection.store_newer_action'],
]);
