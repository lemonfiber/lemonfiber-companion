<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AFileReshipped;
use Modules\Kernel\Api\AMoveInPlace;
use Modules\Kernel\Api\AnInstallAStartOffers;
use Modules\Kernel\Api\APlaceholder;
use Modules\Kernel\Api\APlaceholderForm;
use Modules\Kernel\Api\APluginChange;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\APrivilegedShape;
use Modules\Kernel\Api\AShapeTaken;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheFilesReshipped;
use Modules\Kernel\Api\TheInstallsAStartOffers;
use Modules\Kernel\Api\ThePlaceholders;
use Modules\Kernel\Api\WhatAChangePuts;
use Modules\Kernel\Api\WhatBecameOfAMoveInPlace;
use Modules\Kernel\Api\WhatBecameOfTheMovesInPlace;
use Modules\Kernel\Api\WhatElseAVerbSaid;

it('holds a placeholder by its secret and its form, and refuses a blank secret', function (): void {
    $placeholder = APlaceholder::naming('api_key', APlaceholderForm::Pbkdf2);

    expect($placeholder->secret())->toBe('api_key')
        ->and($placeholder->form())->toBe(APlaceholderForm::Pbkdf2)
        ->and(APlaceholderForm::Plain->saidOnTheScreen())->toBe('plugins.placeholder_form.plain')
        ->and(static fn(): APlaceholder => APlaceholder::naming(' ', APlaceholderForm::Plain))->toThrow(PluginSaysNothing::class, '`secret`');
});

it('holds a starting file an update reshipped by what it was and what it becomes, and refuses a blank path', function (): void {
    $was = ThePlaceholders::these(APlaceholder::naming('api_key', APlaceholderForm::Plain));
    $becomes = ThePlaceholders::these();
    $file = AFileReshipped::at('/srv/config.xml', $was, $becomes);

    expect($file->path())->toBe('/srv/config.xml')
        ->and($file->was())->toBe($was)
        ->and($file->becomes())->toBe($becomes)
        ->and(iterator_to_array(TheFilesReshipped::these($file), preserve_keys: false))->toBe([$file])
        ->and(TheFilesReshipped::these($file))->toHaveCount(1)
        ->and(static fn(): AFileReshipped => AFileReshipped::at(' ', $was, $becomes))->toThrow(PluginSaysNothing::class, '`path`');
});

it('names a change\'s placeholders only once told them', function (): void {
    $placeholders = ThePlaceholders::these(APlaceholder::naming('api_key', APlaceholderForm::Plain));
    $change = APluginChange::at('/srv/config.xml', WhatAChangePuts::Starting);
    $named = $change->naming($placeholders);

    expect($change->placeholders())->toHaveCount(0)
        ->and($named->placeholders())->toBe($placeholders)
        ->and($named->path())->toBe('/srv/config.xml')
        ->and($named->puts())->toBe(WhatAChangePuts::Starting);
});

it('says a shape adds its service to its devices\' group only once told', function (): void {
    $shape = AShapeTaken::by('jellyfin', APrivilegedShape::Render, PluginLines::none(), PluginLines::under('devices', '/dev/dri'), 'render@jellyfin');
    $joining = $shape->joiningTheDevicesGroup();

    expect($shape->joinsTheDevicesGroup())->toBeFalse()
        ->and($joining->joinsTheDevicesGroup())->toBeTrue()
        ->and($joining->service())->toBe('jellyfin')
        ->and($joining->approval())->toBe('render@jellyfin')
        ->and([...$joining->devices()])->toBe(['/dev/dri']);
});

it('holds an install a start offers by its plugin, trims why, and refuses a blank plugin', function (): void {
    $install = AnInstallAStartOffers::of('plugin-jellyfin', ' It could not be read ');

    expect($install->plugin())->toBe('plugin-jellyfin')
        ->and($install->why())->toBe('It could not be read')
        ->and(iterator_to_array(TheInstallsAStartOffers::these($install), preserve_keys: false))->toBe([$install])
        ->and(TheInstallsAStartOffers::these($install))->toHaveCount(1)
        ->and(static fn(): AnInstallAStartOffers => AnInstallAStartOffers::of(' ', ''))->toThrow(PluginSaysNothing::class, '`plugin`');
});

it('says nothing beside a verb until told, and then each thing as told', function (): void {
    $guards = WhatBecameOfTheMovesInPlace::these(WhatBecameOfAMoveInPlace::taken(AMoveInPlace::of(ServiceId::called('decline'), APluginId::called('plugin-decline'))));
    $installs = TheInstallsAStartOffers::these(AnInstallAStartOffers::of('plugin-jellyfin', ''));
    $without = PluginLines::under('unrendered', 'jellyfin runs without hardware transcoding');
    $said = WhatElseAVerbSaid::these($guards, $installs, $without, 'The host has no render group');

    expect(WhatElseAVerbSaid::nothing()->guards())->toHaveCount(0)
        ->and(WhatElseAVerbSaid::nothing()->installs())->toHaveCount(0)
        ->and(WhatElseAVerbSaid::nothing()->withoutRender()->isEmpty())->toBeTrue()
        ->and(WhatElseAVerbSaid::nothing()->whyNoRender())->toBe('')
        ->and($said->guards())->toBe($guards)
        ->and($said->installs())->toBe($installs)
        ->and($said->withoutRender())->toBe($without)
        ->and($said->whyNoRender())->toBe('The host has no render group');
});
