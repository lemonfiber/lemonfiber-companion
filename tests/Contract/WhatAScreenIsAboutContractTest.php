<?php

declare(strict_types=1);

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
use Modules\Stacks\Api\AStacksScreen;

/**
 * One of every kind of thing a screen under a stack can be about, each named
 * with a space and a slash where its kind allows them.
 *
 * @return array<string, array{WhatAScreenIsAbout}>
 */
function everythingAScreenCanBeAbout(): array
{
    return [
        'a service' => [ServiceId::called('a service/2')],
        'a whole form' => [Form::called('arr')],
        'a word' => [AWordInUse::named('down/up ratio')],
        'a check' => [Check::of('disk/free space')],
        'a run of every check' => [WhatARunCovers::everyCheck()],
        'a run of one family' => [WhatARunCovers::theFamily(Category::Storage)],
        'a title on the shelf' => [HoldingId::called('a1 b/2')],
        'a title a search found' => [AFoundTitle::listed('tmdb:1 2/3', KindOfTitle::Film, 'Alien', WhenItCameOut::unstated(), WhereAFoundTitleStands::Askable)],
        'an ask' => [RequestId::named('ask 1/2')],
        'an item to follow' => [WhatToFollow::called('AC/DC Live')],
        'a copy' => [ACopy::named('lemonfiber 2026/09/24')],
        'a plugin' => [APluginId::called('a plugin/2')],
        'a run on the record' => [ARun::stamped('17 90')],
        'a download' => [ADownloadHeld::named('Show Season1/Extras')],
        'pausing the downloads' => [WhatToDoWithTheDownloads::Pause],
        'resuming the downloads' => [WhatToDoWithTheDownloads::Resume],
        'a member' => [SomebodyInTheHousehold::called('Anna Maria/2')],
    ];
}

it('names what a screen is about with text that is not blank', function (WhatAScreenIsAbout $about): void {
    expect(trim($about->namedInAPath()))->not->toBe('');
})->with(everythingAScreenCanBeAbout());

it('names what a screen is about in one segment of its path, which reads back as the name', function (WhatAScreenIsAbout $about): void {
    $segments = explode('/', AStacksScreen::Logs->forTheStacks(StackId::rememberedAs('the-machine'), $about));

    expect($segments)->toHaveCount(5)
        ->and(rawurldecode($segments[4]))->toBe($about->namedInAPath());
})->with(everythingAScreenCanBeAbout());
