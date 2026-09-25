<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function implode;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\TheFrontDoor;
use Modules\Kernel\Api\WhatItFaces;
use Modules\Sdk\Api\FrontDoorIsUnreadable;
use Modules\Sdk\Api\WhereTheDoorIs;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

/**
 * A `front-door` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function frontDoorSaying(mixed $data): Envelope
{
    return new Envelope(1, 'front-door', $data);
}

/**
 * A door worked out from what the stack declares, with nothing beside it and no address.
 *
 * @return array<string, mixed>
 */
function aPlainDoor(): array
{
    return [
        'standing' => 'established',
        'meaning' => 'Send people to Jellyseerr',
        'chosen' => ['chosen' => 'derived'],
        'service' => 'Jellyseerr',
        'facing' => 'asking',
        'beside' => [],
    ];
}

/**
 * One service beside the door, with no address.
 *
 * @return array<string, mixed>
 */
function aPlainServiceBeside(): array
{
    return ['service' => 'Jellyfin', 'facing' => 'watching', 'because' => 'Nothing can be asked for there'];
}

/** One line carried out of an arm. */
final readonly class WhatTheDoorCarried
{
    public function __construct(public string $said) {}
}

/**
 * Everything a payload says, as lines.
 *
 * @param array<string, mixed> $data
 */
function theDoorRead(array $data): string
{
    $door = WhereTheDoorIs::in(frontDoorSaying($data));
    $lines = [
        sprintf('%s|%s', $door->standing()->value, $door->meaning()),
        sprintf('%s|%s|%s', $door->chosen()->how()->value, $door->chosen()->named(), $door->chosen()->because()),
        $door->begins()->either(
            at: static fn(string $service, WhatItFaces $facing, AnAddressToHand $address): WhatTheDoorCarried => new WhatTheDoorCarried(sprintf('at %s|%s|%s|%s', $service, $facing->value, $address->url(), $address->caution())),
            nowhere: static fn(): WhatTheDoorCarried => new WhatTheDoorCarried('nowhere'),
        )->said,
    ];

    foreach ($door->beside() as $service) {
        $lines[] = sprintf('%s|%s|%s|%s|%s', $service->service(), $service->facing()->value, $service->because(), $service->address()->url(), $service->address()->caution());
    }

    return implode("\n", $lines);
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('FrontDoorEnvelope', ['api_version' => 1, 'kind' => 'front-door', 'data' => [...aPlainDoor(), 'beside' => [aPlainServiceBeside()]]]))->toBe([]);
});

it('reads a door worked out from what the stack declares, with no address', function (): void {
    expect(theDoorRead(aPlainDoor()))->toBe("established|Send people to Jellyseerr\nderived||\nat Jellyseerr|asking||");
});

it('reads every address as the stack sent it, with its caution, and absent or null as none', function (): void {
    $beside = [...aPlainServiceBeside(), 'address' => ['url' => 'http://loft.local:8096', 'caution' => null]];

    expect(theDoorRead([...aPlainDoor(), 'address' => ['url' => 'http://loft.local:5055', 'caution' => 'Home only'], 'beside' => [$beside]]))
        ->toBe("established|Send people to Jellyseerr\nderived||\nat Jellyseerr|asking|http://loft.local:5055|Home only\nJellyfin|watching|Nothing can be asked for there|http://loft.local:8096|")
        ->and(theDoorRead([...aPlainDoor(), 'address' => null, 'beside' => [[...aPlainServiceBeside(), 'address' => null]]]))
        ->toBe("established|Send people to Jellyseerr\nderived||\nat Jellyseerr|asking||\nJellyfin|watching|Nothing can be asked for there||");
});

it('reads a stack with no service as nowhere, whatever else it sent', function (): void {
    expect(theDoorRead([...array_diff_key(aPlainDoor(), ['service' => true, 'facing' => true]), 'standing' => 'none']))->toContain("\nnowhere")
        ->and(theDoorRead([...aPlainDoor(), 'service' => null, 'facing' => null, 'address' => ['url' => 'http://loft.local:5055']]))->toContain("\nnowhere");
});

it('reads a door the operator named, and one they named that was refused', function (): void {
    expect(theDoorRead([...aPlainDoor(), 'chosen' => ['chosen' => 'named', 'door' => 'jellyseerr']]))->toContain("\nnamed|jellyseerr|\n")
        ->and(theDoorRead([...aPlainDoor(), 'chosen' => ['chosen' => 'refused', 'door' => ['named' => 'homepage', 'because' => 'It lists everything']]]))->toContain("\nrefused|homepage|It lists everything\n")
        ->and(theDoorRead([...aPlainDoor(), 'chosen' => ['chosen' => 'derived', 'door' => 'homepage']]))->toContain("\nderived||\n");
});

it('reads every standing and everything a service can face', function (string $standing, string $facing): void {
    expect(theDoorRead([...aPlainDoor(), 'standing' => $standing, 'facing' => $facing]))->toContain(sprintf('%s|', $standing))
        ->and(theDoorRead([...aPlainDoor(), 'standing' => $standing, 'facing' => $facing]))->toContain(sprintf('|%s|', $facing));
})->with([
    ['library-only', 'watching'], ['unreachable', 'shelf'], ['stranded', 'operators'], ['established', 'carriage'], ['established', 'unstated'],
]);

it('refuses a payload with no data', function (): void {
    expect(fn(): TheFrontDoor => WhereTheDoorIs::in(frontDoorSaying('nothing')))->toThrow(FrontDoorIsUnreadable::class, '`data`');
});

it('refuses a field of the door that is missing, blank or not what the contract says', function (string $field, mixed $said): void {
    $data = $said === 'absent' ? array_diff_key(aPlainDoor(), [$field => true]) : [...aPlainDoor(), $field => $said];

    expect(fn(): TheFrontDoor => WhereTheDoorIs::in(frontDoorSaying($data)))->toThrow(FrontDoorIsUnreadable::class, sprintf('The front-door envelope has no readable `%s`', $field));
})->with([
    ['standing', 'absent'], ['meaning', ' '], ['meaning', 7], ['chosen', 'absent'], ['chosen', 'derived'],
    ['service', ' '], ['facing', 'absent'], ['beside', 'absent'], ['beside', 'nothing'], ['address', 'http://loft.local'],
]);

it('refuses how the door was chosen where it does not say what its arm owes', function (mixed $chosen, string $path): void {
    expect(fn(): TheFrontDoor => WhereTheDoorIs::in(frontDoorSaying([...aPlainDoor(), 'chosen' => $chosen])))->toThrow(FrontDoorIsUnreadable::class, sprintf('no readable `%s`', $path));
})->with([
    [['door' => 'jellyseerr'], 'chosen'],
    [['chosen' => 'named'], 'door'],
    [['chosen' => 'named', 'door' => ' '], 'door'],
    [['chosen' => 'refused', 'door' => 'homepage'], 'chosen.door'],
    [['chosen' => 'refused'], 'chosen.door'],
    [['chosen' => 'refused', 'door' => ['because' => 'It lists everything']], 'door.named'],
    [['chosen' => 'refused', 'door' => ['named' => 'homepage', 'because' => ' ']], 'door.because'],
]);

it('refuses an address that does not say where', function (mixed $address, string $path): void {
    expect(fn(): TheFrontDoor => WhereTheDoorIs::in(frontDoorSaying([...aPlainDoor(), 'address' => $address])))->toThrow(FrontDoorIsUnreadable::class, sprintf('no readable `%s`', $path));
})->with([
    [['caution' => 'Home only'], 'address.url'],
    [['url' => ' '], 'address.url'],
    [['url' => 'http://loft.local:5055', 'caution' => ' '], 'address.caution'],
    [['url' => 'http://loft.local:5055', 'caution' => 7], 'address.caution'],
]);

it('refuses a word it has no case for, naming the field and every word it reads', function (mixed $data, string $field, string $said, string $accepts): void {
    expect(fn(): TheFrontDoor => WhereTheDoorIs::in(frontDoorSaying($data)))
        ->toThrow(FrontDoorIsUnreadable::class, sprintf('says `%s` is `%s`, and this app reads %s.', $field, $said, $accepts));
})->with([
    [[...aPlainDoor(), 'standing' => 'ajar'], 'standing', 'ajar', '`established`, `library-only`, `unreachable`, `stranded`, `none`'],
    [[...aPlainDoor(), 'chosen' => ['chosen' => 'guessed']], 'chosen', 'guessed', '`derived`, `named`, `refused`'],
    [[...aPlainDoor(), 'facing' => 'nobody'], 'facing', 'nobody', '`asking`, `watching`, `shelf`, `operators`, `carriage`, `unstated`'],
    [[...aPlainDoor(), 'beside' => [[...aPlainServiceBeside(), 'facing' => 'nobody']]], 'facing', 'nobody', '`asking`, `watching`, `shelf`, `operators`, `carriage`, `unstated`'],
]);

it('refuses a service beside the door that does not say what it owes, by its position', function (string $field, mixed $row): void {
    expect(fn(): TheFrontDoor => WhereTheDoorIs::in(frontDoorSaying([...aPlainDoor(), 'beside' => [aPlainServiceBeside(), $row]])))
        ->toThrow(FrontDoorIsUnreadable::class, sprintf('Entry 1 of `beside` in the front-door envelope has no readable `%s`', $field));
})->with([
    ['service', 'Jellyfin'],
    ['service', array_diff_key(aPlainServiceBeside(), ['service' => true])],
    ['facing', [...aPlainServiceBeside(), 'facing' => 7]],
    ['because', [...aPlainServiceBeside(), 'because' => ' ']],
]);
