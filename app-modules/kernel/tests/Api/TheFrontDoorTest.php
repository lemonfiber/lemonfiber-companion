<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AServiceBeside;
use Modules\Kernel\Api\HowTheDoorCameToBe;
use Modules\Kernel\Api\HowTheDoorWasChosen;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheDoorSaysNothing;
use Modules\Kernel\Api\TheFrontDoor;
use Modules\Kernel\Api\TheServicesBeside;
use Modules\Kernel\Api\WhatItFaces;
use Modules\Kernel\Api\WhatWasFoundOfTheFrontDoor;
use Modules\Kernel\Api\WhereTheFrontDoorStands;
use Modules\Kernel\Api\WhereTheHouseholdBegins;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheDoorTook
{
    public function __construct(public string $said) {}
}

/** Where the household begins, folded to one line. */
function whereTheyBegin(WhereTheHouseholdBegins $begins): string
{
    return $begins->either(
        at: static fn(string $service, WhatItFaces $facing, AnAddressToHand $address): WhichArmTheDoorTook => new WhichArmTheDoorTook(sprintf('%s|%s|%s', $service, $facing->value, $address->url())),
        nowhere: static fn(): WhichArmTheDoorTook => new WhichArmTheDoorTook('nowhere'),
    )->said;
}

/** A door meaning what is given here. */
function aDoorMeaning(string $meaning): TheFrontDoor
{
    return TheFrontDoor::reported(WhereTheFrontDoorStands::None, $meaning, HowTheDoorCameToBe::derived(), WhereTheHouseholdBegins::nowhere(), TheServicesBeside::of());
}

it('keeps everything it was reported with', function (): void {
    $chosen = HowTheDoorCameToBe::derived();
    $begins = WhereTheHouseholdBegins::nowhere();
    $beside = TheServicesBeside::of();
    $door = TheFrontDoor::reported(WhereTheFrontDoorStands::Stranded, 'It answers and cannot be found', $chosen, $begins, $beside);

    expect([$door->standing(), $door->meaning(), $door->chosen(), $door->begins(), $door->beside()])
        ->toBe([WhereTheFrontDoorStands::Stranded, 'It answers and cannot be found', $chosen, $begins, $beside]);
});

it('refuses a door that will not say what it means', function (): void {
    expect(fn(): TheFrontDoor => aDoorMeaning(' '))->toThrow(TheDoorSaysNothing::class, 'arrived with its `meaning` blank');
});

it('says how the door came to be, with what was named and why it was refused', function (): void {
    $derived = HowTheDoorCameToBe::derived();
    $named = HowTheDoorCameToBe::byTheOperator('jellyseerr');
    $refused = HowTheDoorCameToBe::refused('homepage', 'It lists everything');

    expect([$derived->how(), $derived->named(), $derived->because()])->toBe([HowTheDoorWasChosen::Derived, '', ''])
        ->and([$named->how(), $named->named(), $named->because()])->toBe([HowTheDoorWasChosen::Named, 'jellyseerr', ''])
        ->and([$refused->how(), $refused->named(), $refused->because()])->toBe([HowTheDoorWasChosen::Refused, 'homepage', 'It lists everything']);
});

it('refuses a named door that names nothing, and a refusal that will not say what or why', function (): void {
    expect(fn(): HowTheDoorCameToBe => HowTheDoorCameToBe::byTheOperator(' '))->toThrow(TheDoorSaysNothing::class, '`door`')
        ->and(fn(): HowTheDoorCameToBe => HowTheDoorCameToBe::refused('', 'It lists everything'))->toThrow(TheDoorSaysNothing::class, '`named`')
        ->and(fn(): HowTheDoorCameToBe => HowTheDoorCameToBe::refused('homepage', ' '))->toThrow(TheDoorSaysNothing::class, '`because`');
});

it('keeps an address as it was sent, and none as nothing', function (): void {
    $address = AnAddressToHand::at('http://loft.local:5055', 'Home only');

    expect([$address->url(), $address->caution()])->toBe(['http://loft.local:5055', 'Home only'])
        ->and(AnAddressToHand::at('http://loft.local:5055', '')->caution())->toBe('')
        ->and([AnAddressToHand::none()->url(), AnAddressToHand::none()->caution()])->toBe(['', '']);
});

it('refuses an address that says nothing, and a caution that is blank rather than empty', function (): void {
    expect(fn(): AnAddressToHand => AnAddressToHand::at(' ', ''))->toThrow(TheDoorSaysNothing::class, '`url`')
        ->and(fn(): AnAddressToHand => AnAddressToHand::at('http://loft.local:5055', ' '))->toThrow(TheDoorSaysNothing::class, '`caution`');
});

it('begins somewhere with a service, what it faces and its address, or nowhere', function (): void {
    expect(whereTheyBegin(WhereTheHouseholdBegins::at('Jellyseerr', WhatItFaces::Asking, AnAddressToHand::at('http://loft.local:5055', ''))))->toBe('Jellyseerr|asking|http://loft.local:5055')
        ->and(whereTheyBegin(WhereTheHouseholdBegins::nowhere()))->toBe('nowhere')
        ->and(fn(): WhereTheHouseholdBegins => WhereTheHouseholdBegins::at(' ', WhatItFaces::Asking, AnAddressToHand::none()))->toThrow(TheDoorSaysNothing::class, '`service`');
});

it('keeps a service beside the door, and refuses one that will not say what it is or why', function (): void {
    $address = AnAddressToHand::none();
    $service = AServiceBeside::said('Jellyfin', WhatItFaces::Watching, 'Nothing to ask for there', $address);

    expect([$service->service(), $service->facing(), $service->because(), $service->address()])->toBe(['Jellyfin', WhatItFaces::Watching, 'Nothing to ask for there', $address])
        ->and(fn(): AServiceBeside => AServiceBeside::said(' ', WhatItFaces::Watching, 'Nothing to ask for there', $address))->toThrow(TheDoorSaysNothing::class, '`service`')
        ->and(fn(): AServiceBeside => AServiceBeside::said('Jellyfin', WhatItFaces::Watching, '', $address))->toThrow(TheDoorSaysNothing::class, '`because`');
});

it('keeps every service beside the door in the stack\'s order', function (): void {
    $beside = TheServicesBeside::of(...[
        'first' => AServiceBeside::said('Jellyfin', WhatItFaces::Watching, 'Nothing to ask for', AnAddressToHand::none()),
        'second' => AServiceBeside::said('Homepage', WhatItFaces::Operators, 'It shows everything', AnAddressToHand::none()),
    ]);
    $named = [];

    foreach ($beside as $service) {
        $named[] = $service->service();
    }

    expect($named)->toBe(['Jellyfin', 'Homepage'])
        ->and(array_keys(iterator_to_array($beside, preserve_keys: true)))->toBe([0, 1])
        ->and($beside)->toHaveCount(2);
});

it('says each standing, facing and choice under a key of its own', function (): void {
    expect(WhereTheFrontDoorStands::LibraryOnly->saidOnTheScreen())->toBe('stacks.front_door.standing.library-only')
        ->and(WhatItFaces::Carriage->saidOnTheScreen())->toBe('stacks.front_door.facing.carriage')
        ->and(HowTheDoorWasChosen::Refused->saidOnTheScreen())->toBe('stacks.front_door.chosen.refused');
});

it('a door that could not be read is never a stack with no door', function (): void {
    $fold = static fn(WhatWasFoundOfTheFrontDoor $answer): string => $answer->either(
        found: static fn(TheFrontDoor $door): WhichArmTheDoorTook => new WhichArmTheDoorTook(sprintf('found:%s', $door->standing()->value)),
        met: static fn(Obstacle $why): WhichArmTheDoorTook => new WhichArmTheDoorTook(sprintf('met:%s', $why->value)),
    )->said;

    expect($fold(WhatWasFoundOfTheFrontDoor::found(aDoorMeaning('Nothing is open'))))->toBe('found:none')
        ->and($fold(WhatWasFoundOfTheFrontDoor::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
