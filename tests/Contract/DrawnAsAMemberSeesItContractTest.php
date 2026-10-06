<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\DrawnAsAMemberSeesIt;
use Modules\Design\Api\WhoseTheme;
use Modules\Household\Internal\Screens\WhatAMemberWouldSee;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whose;
use Native\Mobile\Edge\NativeComponent;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AScreenAMemberWouldSee;

// The DrawnAsAMemberSeesIt contract, run against every screen drawn as a member
// sees it and against the fake: built as the router builds it, about a stack
// this phone holds the operator's session for, and brought to the front over
// the operator's theme, it is drawn in the member's.

/** The stack the operator's preview is opened on. */
function theStackTheOperatorPreviews(): StackId
{
    return StackId::of(Nonce::of(str_repeat('p', Nonce::SHORTEST)));
}

/** The preview, built the way the router builds it, about the stack named. */
function thePreviewBuiltAbout(StackId $stack): WhatAMemberWouldSee
{
    $built = app(WhatAMemberWouldSee::class);
    $built->setParams(['stack' => $stack->stored()]);

    return $built;
}

/** @return array<string, callable(StackId): (NativeComponent&DrawnAsAMemberSeesIt)> */
function screensDrawnAsAMemberSeesThem(): array
{
    return [
        'WhatAMemberWouldSee' => thePreviewBuiltAbout(...),
        'AScreenAMemberWouldSee' => AScreenAMemberWouldSee::about(...),
    ];
}

foreach (screensDrawnAsAMemberSeesThem() as $name => $build) {
    it(sprintf('%s is drawn in the member\'s theme on the operator\'s session', $name), function () use ($build): void {
        $keychain = AKeychainInMemory::working();
        $keychain->keep(theStackTheOperatorPreviews(), Session::of('a-session-not-a-secret'), Whose::theOperator());

        $glass = new TheTheme(static fn(): SecureStorage => $keychain);
        $glass->paint(WhoseTheme::Operator);

        $glass->forTheScreen($build(theStackTheOperatorPreviews()));

        expect($glass->whose())->toBe(WhoseTheme::Member);
    });
}
