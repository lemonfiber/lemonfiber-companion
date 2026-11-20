<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\OutOfTheOperatorsScreens;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\ACertificateName;
use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItIsServed;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheCertificateItPresents;
use Modules\Kernel\Api\TheNamesItCovers;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\HowThisStackIsServed;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatSaysHowItIsServed;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\TheHealthScreenOfTheLoft;
use Tests\Support\WhatTheDeviceWouldDraw;

/** The moment the phone's clock reads, as `AroundThePhone` freezes it. */
const THE_PHONES_MOMENT = 1_790_000_000;

/** A day, in seconds. */
const A_DAY = 86_400;

/** The certificate the loft presents: the one this phone pins, signed by a house CA, with two names written out and three left out. */
function theLoftsCertificate(
    string $fingerprint = 'b',
    ?ACertificateName $subject = null,
    bool $selfSigned = false,
): TheCertificateItPresents {
    return TheCertificateItPresents::said(
        Fingerprint::of(str_repeat($fingerprint, Fingerprint::CHARACTERS)),
        $subject ?? ACertificateName::said('CN=loft.home.arpa', cut: false),
        ACertificateName::said('CN=House CA,O=Home', cut: false),
        $selfSigned,
        TheNamesItCovers::these(3, ACertificateName::said('loft.home.arpa', cut: false), ACertificateName::said('192.168.1.42', cut: false)),
        Instant::atEpochSeconds(THE_PHONES_MOMENT - (10 * A_DAY)),
        Instant::atEpochSeconds(THE_PHONES_MOMENT + (90 * A_DAY)),
    );
}

/** The loft, served encrypted on the network under its name, presenting that certificate. */
function theLoftServed(?TheCertificateItPresents $certificate = null, string $unrecorded = ''): HowItIsServed
{
    return HowItIsServed::presenting($certificate ?? theLoftsCertificate(), 8443, network: true, named: 'loft.home.arpa', unrecorded: $unrecorded);
}

/** The screen, on a stack this device is signed in to as its operator, or as nobody. */
function theServedScreen(AStackThatSaysHowItIsServed $served, bool $signedIn = true): HowThisStackIsServed
{
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();

    if ($signedIn) {
        $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());
    }

    $screen = new HowThisStackIsServed(
        $keychain,
        AroundThePhone::holding(StacksInMemory::holding($stack)),
        new AppsSettingsThatOpen(),
        AroundThePhone::listening(),
        $served,
    );
    $screen->setParams(['stack' => $stack->id()->stored()]);

    return $screen;
}

it('draws the name and port it answers on, and the certificate it presents beside the pin this phone holds, saying they are the same', function (): void {
    $served = AStackThatSaysHowItIsServed::with(theLoftServed());
    $said = WhatTheDeviceWouldDraw::by(theServedScreen($served))->said();
    $pinned = AtAGlance::of(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->presents())->shown();

    expect($served->askings())->toBe(1)
        ->and($said)->toContain(
            __('served.named'),
            'loft.home.arpa',
            __('served.port'),
            '8443',
            __('served.encrypted.yes'),
            __('served.network.yes'),
            __('served.pin.presented'),
            __('served.pin.held'),
            $pinned,
            __('served.pin.same'),
            __('served.subject'),
            'CN=loft.home.arpa',
            __('served.issuer'),
            'CN=House CA,O=Home',
            __('served.valid.from'),
            trans_choice('health.ago.days', 10),
            __('served.valid.until'),
            trans_choice('health.in.days', 90),
            'loft.home.arpa',
            '192.168.1.42',
            trans_choice('served.names_more', 3),
            __('served.replacing'),
        )
        ->and(array_count_values($said)[$pinned])->toBe(2)
        ->and($said)->not->toContain(__('served.pin.different'), __('served.self_signed'), __('served.cut'));
});

it('says the certificate is not the one this phone pins, in the comparable form of each', function (): void {
    $said = WhatTheDeviceWouldDraw::by(theServedScreen(AStackThatSaysHowItIsServed::with(theLoftServed(theLoftsCertificate('c')))))->said();

    expect($said)->toContain(
        AtAGlance::of(Fingerprint::of(str_repeat('c', Fingerprint::CHARACTERS)))->shown(),
        AtAGlance::of(TheHealthScreenOfTheLoft::theStackBeingLookedAt()->presents())->shown(),
        __('served.pin.different'),
    )->and($said)->not->toContain(__('served.pin.same'), str_repeat('c', Fingerprint::CHARACTERS));
});

it('draws a subject holding markup and written-out control characters as the plain text it is, and says it was cut short', function (): void {
    $subject = '<b onclick="x()">CN=evil</b>\x1b[31m{{ $secret }}&amp;';
    $said = WhatTheDeviceWouldDraw::by(theServedScreen(AStackThatSaysHowItIsServed::with(theLoftServed(theLoftsCertificate(subject: ACertificateName::said($subject, cut: true))))))->said();

    expect($said)->toContain($subject, __('served.cut'));
});

it('says a certificate that signed itself was signed by itself, and an unencrypted surface presents none', function (): void {
    $selfSigned = WhatTheDeviceWouldDraw::by(theServedScreen(AStackThatSaysHowItIsServed::with(theLoftServed(theLoftsCertificate(selfSigned: true)))))->said();
    $plain = WhatTheDeviceWouldDraw::by(theServedScreen(AStackThatSaysHowItIsServed::with(HowItIsServed::plainly(8080, network: false, named: '', unrecorded: ''))))->said();

    expect($selfSigned)->toContain(__('served.self_signed'))
        ->and($selfSigned)->not->toContain('CN=House CA,O=Home')
        ->and($plain)->toContain('8080', __('served.no_name'), __('served.encrypted.no'), __('served.network.no'), __('served.plain'))
        ->and($plain)->not->toContain(__('served.certificate'), __('served.pin.held'));
});

it('says when the certificate became valid ahead of now and that it has expired', function (): void {
    $ahead = TheCertificateItPresents::said(
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
        ACertificateName::said('CN=loft', cut: false),
        ACertificateName::said('CN=House CA', cut: true),
        selfSigned: false,
        names: TheNamesItCovers::these(0),
        from: Instant::atEpochSeconds(THE_PHONES_MOMENT + (2 * A_DAY)),
        until: Instant::atEpochSeconds(THE_PHONES_MOMENT - (3 * A_DAY)),
    );
    $said = WhatTheDeviceWouldDraw::by(theServedScreen(AStackThatSaysHowItIsServed::with(theLoftServed($ahead))))->said();

    expect($said)->toContain(
        __('served.valid.from_ahead'),
        trans_choice('health.in.days', 2),
        __('served.valid.lapsed'),
        trans_choice('health.ago.days', 3),
        __('served.no_names'),
        __('served.cut'),
    );
});

it('says why where it is served could not be written down, in the stack\'s words', function (): void {
    $said = WhatTheDeviceWouldDraw::by(theServedScreen(AStackThatSaysHowItIsServed::with(theLoftServed(unrecorded: 'the configuration directory is read-only'))))->said();

    expect($said)->toContain(__('served.unrecorded'), 'the configuration directory is read-only');
});

it('reads on open and again when asked, and says what stood in the way or that the session ended', function (): void {
    $served = AStackThatSaysHowItIsServed::with(theLoftServed());
    $screen = theServedScreen($served);
    WhatTheDeviceWouldDraw::by($screen);
    $screen->again();
    WhatTheDeviceWouldDraw::by($screen);

    $why = Obstacle::of(KindOfObstacle::StackDidNotAnswer);
    $met = WhatTheDeviceWouldDraw::by(theServedScreen(AStackThatSaysHowItIsServed::met($why)))->said();
    $unasked = AStackThatSaysHowItIsServed::with(theLoftServed());
    $signedOut = WhatTheDeviceWouldDraw::by(theServedScreen($unasked, signedIn: false))->said();

    expect($served->askings())->toBe(2)
        ->and($met)->toContain(__($why->kind()->said()))
        ->and($met)->not->toContain(__('served.section'))
        ->and($unasked->askings())->toBe(0)
        ->and($signedOut)->not->toContain(__('served.section'));
});

it('builds the Home a member sees in place of the screen for a member session, which never asks how the stack is served', function (): void {
    $stack = TheHealthScreenOfTheLoft::theStackBeingLookedAt();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::member('robin'));
    $built = '';
    $guard = new OutOfTheOperatorsScreens($keychain, ['stack' => $stack->id()->stored()], static function (string $class) use (&$built): string {
        $built = $class;

        return $class;
    });

    $guard->screen(HowThisStackIsServed::class);

    expect($built)->toBe(WhatYouCanWatch::class);
});

it('offers no replacing of the certificate, and says it is replaced at the machine', function (): void {
    $screen = theServedScreen(AStackThatSaysHowItIsServed::with(theLoftServed()));

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(__('served.replacing'))
        ->and(get_class_methods($screen))->not->toContain('replaceTheCertificate');
});
