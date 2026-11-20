<?php

declare(strict_types=1);

use Modules\Codes\Api\QrCodes;
use Modules\Connection\Api\HowTheSignInWent;
use Modules\Connection\Api\RememberingTheWayIn;
use Modules\Connection\Api\TheWayIn;
use Modules\Kernel\Api\ACodeToApprove;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AWaitingCode;
use Modules\Kernel\Api\CodeAsked;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowLong;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\HowTheCodeStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\MustNotLeaveThisProcess;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\WhatTheHouseholdWasTold;
use Modules\Kernel\Api\WhenToTryAgain;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\SigningInByACode;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeRouter;
use Native\Mobile\Edge\NavigationIntent;
use Tests\Support\AroundThePhone;
use Tests\Support\AWayInRemembered;
use Tests\Support\Fakes\ADoorThatGivesCodes;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\AZoneThatIsSet;
use Tests\Support\Fakes\PagesThatOpen;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

const WHEN_THE_CODE_LAPSES = 1_789_380_900;

const THE_HANDLE_NOBODY_SEES = 'a-handle-that-opens-a-session';

const THE_PAGE_TO_APPROVE_AT = 'https://approve.example/link';

function aHouseThatSignsInByACode(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

function aCodeToApprove(): ACodeToApprove
{
    return ACodeToApprove::issued('WXYZ', THE_PAGE_TO_APPROVE_AT, Instant::atEpochSeconds(WHEN_THE_CODE_LAPSES), AWaitingCode::answered(THE_HANDLE_NOBODY_SEES));
}

function theHousesWords(): WhatTheHouseholdWasTold
{
    return WhatTheHouseholdWasTold::said('That code is no longer waiting.', 'Ask for a new one.');
}

/**
 * The screen, opened, with a door, a store, a browser and a sharing sheet that answer as a test says.
 *
 * @return array{SigningInByACode, AKeychainInMemory}
 */
function theCodeScreen(
    ADoorThatGivesCodes $door,
    ?PagesThatOpen $pages = null,
    ?AShareSheetThatWasOffered $sheet = null,
    ?RememberingTheWayIn $remembered = null,
): array {
    $house = aHouseThatSignsInByACode();
    $keychain = AKeychainInMemory::working();
    $screen = new SigningInByACode(
        $door,
        $keychain,
        $remembered ?? AWayInRemembered::nowhere(),
        $pages ?? new PagesThatOpen(),
        $sheet ?? AShareSheetThatWasOffered::working(),
        new QrCodes(),
        AZoneThatIsSet::to('UTC'),
        app('translator'),
        AroundThePhone::holding(StacksInMemory::holding($house), storage: $keychain),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
    );
    $screen->setParams(['stack' => $house->id()->stored()]);
    $screen->theCode();

    return [$screen, $keychain];
}

it('asks for a code as it opens, and shows it with a way to copy it, the page as a button and as a code to scan, and when it lapses', function (): void {
    $door = ADoorThatGivesCodes::issuing(aCodeToApprove());
    [$screen] = theCodeScreen($door);
    $drawn = WhatTheDeviceWouldDraw::by($screen);
    $lapses = AZoneThatIsSet::to('UTC')->zone()->timeOfDayAt(Instant::atEpochSeconds(WHEN_THE_CODE_LAPSES))->shownToTheMinute();

    expect($door->asks())->toBe(1)
        ->and($drawn->said())->toContain('WXYZ', __('connection.code_lapses', ['time' => $lapses]), __('connection.code_scan'))
        ->and($drawn->offers())->toContain(__('connection.code_copy'), __('connection.code_open'))
        ->and($screen->theCode()?->squares)->not->toBe([]);
});

it('asks the house every three seconds while it is shown, and nothing before a code is', function (): void {
    $poll = new ReflectionMethod(SigningInByACode::class, 'whileTheCodeWaits')->getAttributes(Poll::class)[0]->getArguments();
    $door = ADoorThatGivesCodes::answering(CodeAsked::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer), WhenToTryAgain::unsaid()));
    [$screen] = theCodeScreen($door);
    $screen->whileTheCodeWaits();

    expect($poll)->toBe([HowOftenAScreenLooks::WHILE_A_CODE_WAITS_MS])
        ->and(HowOftenAScreenLooks::WhileACodeWaits->seconds())->toBe(3)
        ->and($door->finishes())->toBe(0);
});

it('lands on Home once the code was approved, keeping the session and remembering the code let them in', function (): void {
    $remembered = AWayInRemembered::nowhere();
    $door = ADoorThatGivesCodes::issuing(
        aCodeToApprove(),
        HowTheCodeStands::waiting(Instant::atEpochSeconds(WHEN_THE_CODE_LAPSES)),
        HowTheCodeStands::opened(Session::of('a-session-not-a-secret'), Instant::atEpochSeconds(WHEN_THE_CODE_LAPSES * 2), Whose::member('robin')),
    );
    [$screen, $keychain] = theCodeScreen($door, remembered: $remembered);
    $house = aHouseThatSignsInByACode()->id();

    $screen->whileTheCodeWaits();
    $stillWaiting = [$screen->went, $screen->getNavigationIntent()];
    $screen->whileTheCodeWaits();

    expect($stillWaiting)->toBe([HowTheSignInWent::NotYet, null])
        ->and($door->finishes())->toBe(2)
        ->and($door->finishedWith())->toBe(THE_HANDLE_NOBODY_SEES)
        ->and($screen->went)->toBe(HowTheSignInWent::SignedIn)
        ->and($keychain->isHolding($house))->toBeTrue()
        ->and($screen->theCode())->toBeNull()
        ->and($screen->getNavigationIntent()?->type)->toBe(NavigationIntent::RESET)
        ->and($screen->getNavigationIntent()?->uri)->toBe(AStacksScreen::Shelf->forTheStack($house))
        ->and(NativeRouter::resolve(AStacksScreen::Shelf->forTheStack($house)))->not->toBeNull()
        ->and($remembered->ledWithOn($house))->toBe(TheWayIn::Code);
});

it('shows a code the house no longer waits on in the house\'s words, with a way to ask for a new one', function (): void {
    $door = ADoorThatGivesCodes::issuing(aCodeToApprove(), HowTheCodeStands::lapsed(theHousesWords()));
    [$screen] = theCodeScreen($door);
    $screen->whileTheCodeWaits();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    $screen->whileTheCodeWaits();
    $finishedAfterItLapsed = $door->finishes();
    $screen->askForACode();

    expect($drawn->said())->toContain('That code is no longer waiting.', 'Ask for a new one.')
        ->and($drawn->said())->not->toContain('WXYZ')
        ->and($drawn->offers())->toContain(__('connection.code_new'))
        ->and($finishedAfterItLapsed)->toBe(1)
        ->and($door->asks())->toBe(2)
        ->and($screen->theCode()?->code)->toBe('WXYZ');
});

it('says a code is no longer waiting, or that the house signs in by password, in its own words where the house gave none', function (): void {
    $door = ADoorThatGivesCodes::issuing(aCodeToApprove(), HowTheCodeStands::lapsed(WhatTheHouseholdWasTold::nothing()));
    [$lapsed] = theCodeScreen($door);
    $lapsed->whileTheCodeWaits();
    $lapsed->whileTheCodeWaits();
    [$byPassword] = theCodeScreen(ADoorThatGivesCodes::answering(CodeAsked::byPassword(WhatTheHouseholdWasTold::nothing())));

    expect(WhatTheDeviceWouldDraw::by($lapsed)->said())->toContain(__('connection.code_no_longer_waits'))
        ->and(WhatTheDeviceWouldDraw::by($lapsed)->offers())->toContain(__('connection.code_new'))
        ->and($door->finishes())->toBe(1)
        ->and(WhatTheDeviceWouldDraw::by($byPassword)->said())->toContain(__('connection.code_signs_in_by_password'))
        ->and(WhatTheDeviceWouldDraw::by($byPassword)->offers())->toContain(__('connection.code_by_password'));
});

it('answers a house that signs in by password in its words, with the way back to the password', function (): void {
    [$screen] = theCodeScreen(ADoorThatGivesCodes::answering(CodeAsked::byPassword(WhatTheHouseholdWasTold::said('This house signs in with a password.', 'Use your password.'))));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    $screen->usePassword();

    expect($drawn->said())->toContain('This house signs in with a password.', 'Use your password.')
        ->and($drawn->offers())->toContain(__('connection.code_by_password'))
        ->and($drawn->offers())->not->toContain(__('connection.code_new'))
        ->and($screen->getNavigationIntent()?->type)->toBe(NavigationIntent::BACK);
});

it('says how long to wait where too many codes were asked for, and keeps the way to a new one', function (): void {
    [$screen] = theCodeScreen(ADoorThatGivesCodes::answering(CodeAsked::refused(Obstacle::of(KindOfObstacle::TooManyAttempts), WhenToTryAgain::after(HowLong::ofSeconds(120)))));
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($screen->went)->toBe(HowTheSignInWent::TooManyAttempts)
        ->and($screen->wait?->count)->toBe(2)
        ->and($drawn->offers())->toContain(__('connection.code_new'));
});

it('keeps the code shown where the house did not answer one ask, and lets go of what stood in the way once it answers again', function (): void {
    $door = ADoorThatGivesCodes::issuing(
        aCodeToApprove(),
        HowTheCodeStands::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer), WhenToTryAgain::unsaid()),
        HowTheCodeStands::waiting(Instant::atEpochSeconds(WHEN_THE_CODE_LAPSES)),
    );
    [$screen] = theCodeScreen($door);

    $screen->whileTheCodeWaits();
    $met = [$screen->went, $screen->theCode()?->code];
    $screen->whileTheCodeWaits();

    expect($met)->toBe([HowTheSignInWent::StackDidNotAnswer, 'WXYZ'])
        ->and($screen->went)->toBe(HowTheSignInWent::NotYet)
        ->and($screen->theCode()?->code)->toBe('WXYZ');
});

it('opens the page the code is approved at, and says so where the phone would not', function (): void {
    $pages = new PagesThatOpen();
    [$opened] = theCodeScreen(ADoorThatGivesCodes::issuing(aCodeToApprove()), pages: $pages);
    $opened->openThePage();
    [$refused] = theCodeScreen(ADoorThatGivesCodes::issuing(aCodeToApprove()), pages: PagesThatOpen::wouldNot());
    $refused->openThePage();

    expect($pages->pagesOpened())->toBe([THE_PAGE_TO_APPROVE_AT])
        ->and($opened->pageWouldNotOpen)->toBeFalse()
        ->and($refused->pageWouldNotOpen)->toBeTrue()
        ->and(WhatTheDeviceWouldDraw::by($refused)->said())->toContain(__('connection.code_page_would_not_open'));
});

it('offers the code to copy through the phone\'s own sharing', function (): void {
    $sheet = AShareSheetThatWasOffered::working();
    [$screen] = theCodeScreen(ADoorThatGivesCodes::issuing(aCodeToApprove()), sheet: $sheet);

    $screen->copyTheCode();

    expect($sheet->handed()?->text())->toBe('WXYZ')
        ->and($screen->copied)->toBe('connection.code_copied');
});

it('keeps the handle in memory only: in no public property, dump, export or trace, refused by serialisation, and in nothing the phone keeps', function (): void {
    $remembered = AWayInRemembered::nowhere();
    $door = ADoorThatGivesCodes::issuing(aCodeToApprove(), HowTheCodeStands::opened(Session::of('a-session-not-a-secret'), Instant::atEpochSeconds(WHEN_THE_CODE_LAPSES * 2), Whose::member('robin')));
    [$screen, $keychain] = theCodeScreen($door, remembered: $remembered);
    $public = get_object_vars($screen);
    $printed = [print_r($screen, return: true), var_export(aCodeToApprove(), return: true), (string) json_encode($public), print_r($public, return: true)];

    $screen->whileTheCodeWaits();

    foreach ($printed as $one) {
        expect($one)->not->toContain(THE_HANDLE_NOBODY_SEES);
    }

    expect(static fn(): string => serialize(aCodeToApprove()))->toThrow(MustNotLeaveThisProcess::class)
        ->and(print_r($keychain, return: true))->not->toContain(THE_HANDLE_NOBODY_SEES)
        ->and(print_r($remembered, return: true))->not->toContain(THE_HANDLE_NOBODY_SEES);
});

it('names no service, in any language it speaks', function (): void {
    $lines = ['code_title', 'code_approve', 'code_explained', 'code_copy', 'code_copied', 'code_not_copied', 'code_open', 'code_page_would_not_open', 'code_no_squares', 'code_scan', 'code_lapses', 'code_new', 'code_by_password', 'code_way', 'code_instead', 'code_signs_in_by_password', 'code_no_longer_waits'];

    foreach (['en', 'nl'] as $language) {
        foreach ($lines as $key) {
            $line = __(sprintf('connection.%s', $key), [], $language);

            expect(is_string($line) ? strtolower($line) : '')->not->toBe('')
                ->and(is_string($line) ? strtolower($line) : '')->not->toContain('plex', sprintf('%s %s', $language, $key));
        }
    }
});

it('is reached from the route the sign-in screens send to', function (): void {
    expect(NativeRouter::resolve(AStacksScreen::SignInByCode->forTheStack(aHouseThatSignsInByACode()->id())))->not->toBeNull();
});
