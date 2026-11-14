<?php

declare(strict_types=1);

namespace Modules\Stacks\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Category;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhatARunCovers;
use Modules\Kernel\Api\WhatAScreenIsAbout;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Stacks\Api\AScreenNeedsMoreThanAStack;
use Modules\Stacks\Api\AStacksScreen;

use function sprintf;
use function str_contains;

const A_MACHINE = 'the-machine';

const A_SERVICE = 'the-service';

it('puts the machine in the machine\'s segment and nothing else', function (): void {
    expect(AStacksScreen::Health->forTheStack(StackId::rememberedAs(A_MACHINE)))
        ->toBe(sprintf('/stacks/%s', A_MACHINE))
        ->and(AStacksScreen::Owed->forTheStack(StackId::rememberedAs(A_MACHINE)))
        ->toBe(sprintf('/stacks/%s/yours', A_MACHINE));
});

it('puts the machine and what the screen is about each in their own segment, encoded', function (AStacksScreen $screen, WhatAScreenIsAbout $about, string $path): void {
    expect($screen->forTheStacks(StackId::rememberedAs(A_MACHINE), $about))
        ->toBe(sprintf('/stacks/%s/%s', A_MACHINE, $path));
})->with([
    'a service' => [AStacksScreen::Logs, ServiceId::called(A_SERVICE), 'logs/the-service'],
    'a whole form' => [AStacksScreen::Doing, Form::called('arr'), 'do/arr'],
    'a word' => [AStacksScreen::WordAbout, AWordInUse::named('down/up ratio'), 'words/down%2Fup%20ratio'],
    'a check' => [AStacksScreen::Run, Check::of('disk/free space'), 'run/disk%2Ffree%20space'],
    'a run of one family' => [AStacksScreen::RunChecks, WhatARunCovers::theFamily(Category::Storage), 'run-every/storage'],
    'a title on the shelf' => [AStacksScreen::Title, HoldingId::called('a1 b/2'), 'watch/a1%20b%2F2'],
    'a title a search found' => [AStacksScreen::Found, AFoundTitle::listed('tmdb:1 2/3', KindOfTitle::Film, 'Alien', WhenItCameOut::unstated(), WhereAFoundTitleStands::Askable), 'found/tmdb%3A1%202%2F3'],
    'an ask' => [AStacksScreen::Asked, RequestId::named('ask 1/2'), 'asked/ask%201%2F2'],
    'an item to follow' => [AStacksScreen::Trace, WhatToFollow::called('AC/DC Live'), 'trace/AC%2FDC%20Live'],
    'a copy' => [AStacksScreen::PutBack, ACopy::named('lemonfiber 2026/09/24'), 'copies/lemonfiber%202026%2F09%2F24'],
    'a plugin' => [AStacksScreen::PluginSettings, APluginId::called('a plugin/2'), 'plugins/a%20plugin%2F2'],
    'a run on the record' => [AStacksScreen::RunBack, ARun::stamped('17 90'), 'record/17%2090'],
    'a download' => [AStacksScreen::LetGo, ADownloadHeld::named('Show Season1/Extras'), 'room/Show%20Season1%2FExtras'],
    'pausing the downloads' => [AStacksScreen::Downloads, WhatToDoWithTheDownloads::Pause, 'downloads/pause'],
    'a member' => [AStacksScreen::TakeOut, SomebodyInTheHousehold::called('Anna Maria/2'), 'household/Anna%20Maria%2F2'],
]);

it('leaves no placeholder in anything it hands out', function (): void {
    foreach (AStacksScreen::cases() as $screen) {
        $path = $screen->isAboutOneThing()
            ? $screen->forTheStacks(StackId::rememberedAs(A_MACHINE), ServiceId::called(A_SERVICE))
            : $screen->forTheStack(StackId::rememberedAs(A_MACHINE));

        expect(str_contains($path, AStacksScreen::NAMED))->toBeFalse($screen->name)
            ->and(str_contains($path, AStacksScreen::ABOUT))->toBeFalse($screen->name);
    }
});

it('refuses a case that needs more than a machine, and one that needs less', function (): void {
    expect(fn(): string => AStacksScreen::Logs->forTheStack(StackId::rememberedAs(A_MACHINE)))
        ->toThrow(AScreenNeedsMoreThanAStack::class, 'naming a machine is not enough')
        ->and(fn(): string => AStacksScreen::Health->forTheStacks(StackId::rememberedAs(A_MACHINE), ServiceId::called(A_SERVICE)))
        ->toThrow(AScreenNeedsMoreThanAStack::class, 'names only the machine');
});
