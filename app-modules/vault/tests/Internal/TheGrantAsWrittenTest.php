<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Internal;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheGrantIsFor;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\Whose;
use Modules\Vault\Internal\TheGrantAsWritten;

use function sprintf;
use function str_repeat;

use Tests\Support\TheGrantAsSeen;

/** The member and device the grants here are kept for. */
function adaOnThisDevice(string $member = 'ada', string $device = 'this-device'): TheGrantIsFor
{
    return TheGrantIsFor::of(Whose::member($member), ThisDevice::named($device));
}

it('reads back the grant it wrote, and when it lapses', function (): void {
    $read = TheGrantAsWritten::read(TheGrantAsWritten::written(adaOnThisDevice(), AGrant::of(str_repeat('a', 32), Instant::atEpochSeconds(1_000))), adaOnThisDevice());

    expect(TheGrantAsSeen::of($read))->toBe(sprintf('%s until 1000', str_repeat('a', 32)));
});

it('writes the grant in its shape, with the moment as seconds', function (): void {
    expect(TheGrantAsWritten::written(adaOnThisDevice(), AGrant::of(str_repeat('a', 32), Instant::atEpochSeconds(1_000))))
        ->toBe(sprintf('{"shape":1,"for_the_door":"%s","lapses_at":1000,"played_by":"ada","played_on":"this-device"}', str_repeat('a', 32)));
});

it('reads no grant from a value it did not write, or one the door would not accept', function (string $written): void {
    expect(TheGrantAsSeen::of(TheGrantAsWritten::read($written, adaOnThisDevice())))->toBe(TheGrantAsSeen::NONE);
})->with([
    'not a document' => ['a grant'],
    'another shape' => [(string) json_encode(['shape' => 2, 'for_the_door' => str_repeat('a', 32), 'lapses_at' => 1, 'played_by' => 'ada', 'played_on' => 'this-device'])],
    'no grant in it' => [(string) json_encode(['shape' => 1, 'lapses_at' => 1])],
    'no moment in it' => [(string) json_encode(['shape' => 1, 'for_the_door' => str_repeat('a', 32)])],
    'a grant that is not text' => [(string) json_encode(['shape' => 1, 'for_the_door' => 7, 'lapses_at' => 1])],
    'a moment that is not a number' => [(string) json_encode(['shape' => 1, 'for_the_door' => str_repeat('a', 32), 'lapses_at' => 'soon'])],
    'a moment before the epoch' => [(string) json_encode(['shape' => 1, 'for_the_door' => str_repeat('a', 32), 'lapses_at' => -1])],
    'a grant the door would not accept' => [(string) json_encode(['shape' => 1, 'for_the_door' => 'A-GRANT', 'lapses_at' => 1])],
]);

it('reads no grant kept for another member, another device, or under an id the core would not accept', function (string $member, string $device): void {
    $written = (string) json_encode(['shape' => 1, 'for_the_door' => str_repeat('a', 32), 'lapses_at' => 1, 'played_by' => $member, 'played_on' => $device]);

    expect(TheGrantAsSeen::of(TheGrantAsWritten::read($written, adaOnThisDevice())))->toBe(TheGrantAsSeen::NONE);
})->with([
    'another member' => ['grace', 'this-device'],
    'the operator' => ['', 'this-device'],
    'another device' => ['ada', 'another-device'],
    'a device id the core would not accept' => ['ada', 'short'],
]);
