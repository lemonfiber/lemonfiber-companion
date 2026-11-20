<?php

declare(strict_types=1);

use Lemonfiber\Native\Pages;
use Modules\Device\Api\PlatformPages;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\OpeningAPage;
use Modules\Kernel\Api\WhetherThePageOpened;
use Native\Mobile\Testing\FakeBridge;
use Tests\Support\Fakes\PagesThatOpen;
use Tests\Support\TheWordCarriedOut;

// The OpeningAPage contract, run against the adapter and against the fake.

/** The page a member approves a code at. */
function aPageToApproveAt(): AnAddressToHand
{
    return AnAddressToHand::at('https://approve.example/link', '');
}

function overABrowserThatSays(string $outcome): PlatformPages
{
    FakeBridge::disable();
    FakeBridge::enable()->respondTo('Lemonfiber.Page.Open', ['outcome' => $outcome]);

    return new PlatformPages(new Pages());
}

function theWordForWhatOpeningThePageCameTo(WhetherThePageOpened $opened): string
{
    return $opened->either(
        opened: static fn(): TheWordCarriedOut => new TheWordCarriedOut('opened'),
        wouldNot: static fn(): TheWordCarriedOut => new TheWordCarriedOut('would not'),
    )->said;
}

dataset('every phone that opens a page', [
    'the platform' => [fn(): OpeningAPage => overABrowserThatSays('opened')],
    'the fake' => [fn(): OpeningAPage => new PagesThatOpen()],
]);

dataset('every phone that would not open one', [
    'the platform' => [fn(): OpeningAPage => overABrowserThatSays('refused')],
    'the fake' => [fn(): OpeningAPage => PagesThatOpen::wouldNot()],
]);

it('says the page opened where the phone opened it', function (OpeningAPage $pages): void {
    expect(theWordForWhatOpeningThePageCameTo($pages->open(aPageToApproveAt())))->toBe('opened');
})->with('every phone that opens a page');

it('says the page would not open where the phone would not', function (OpeningAPage $pages): void {
    $opened = $pages->open(aPageToApproveAt());

    expect(theWordForWhatOpeningThePageCameTo($opened))->toBe('would not')
        ->and($opened->wouldNotOpen())->toBeTrue();
})->with('every phone that would not open one');

it('hands lemonfiber\'s own call the page\'s address', function (): void {
    FakeBridge::disable();
    $bridge = FakeBridge::enable()->respondTo('Lemonfiber.Page.Open', ['outcome' => 'opened']);

    new PlatformPages(new Pages())->open(aPageToApproveAt());

    $bridge->assertCalled('Lemonfiber.Page.Open', static fn(mixed $params): bool => $params === ['address' => 'https://approve.example/link']);
});

it('the fake remembers every page asked for', function (): void {
    $pages = new PagesThatOpen();
    $pages->open(aPageToApproveAt());

    expect($pages->pagesOpened())->toBe(['https://approve.example/link']);
});
