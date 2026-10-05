<?php

declare(strict_types=1);

use Bootstrap\Composition\NativePHP\ScreenRouter;
use Bootstrap\Composition\NativePHP\TheTheme;
use Modules\Design\Api\ThemeToken;
use Modules\Design\Api\WhichThemeIsOnTheGlass;
use Modules\Design\Api\WhoseTheme;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Whose;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\Events\Screen\ScreenMounted;
use Native\Mobile\Events\Screen\ScreenResumed;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AScreenComingToTheFront;
use Tests\Support\Fakes\AScreenOpenedOverAnother;

// Runs the composition root rather than reading it, so its mutants are judged
// here: see `scripts/mutation.php`.
pest()->group('holds:bootstrap/Composition');

// Whose session a screen is drawn for decides the theme it is drawn in: the
// operator's for the operator's session, the member's for a member's and for
// nobody's. A screen about no stack that is opened over another keeps the
// theme of the one beneath. What is painted is read back from where a screen
// reads it, the parser for classes and the widget theme for the widgets.

const THE_OPERATORS_STACK = 'a';
const A_MEMBERS_STACK = 'b';
const NOBODYS_STACK = 'c';

function aStackSignedInto(string $letter): StackId
{
    return StackId::of(Nonce::of(str_repeat($letter, Nonce::SHORTEST)));
}

/** The one theme on the glass, on a phone holding the operator's session for one stack and a member's for another. */
function theThemeOnAPhoneWithSessions(): TheTheme
{
    $keychain = AKeychainInMemory::working();
    $keychain->keep(aStackSignedInto(THE_OPERATORS_STACK), Session::of('a-session-not-a-secret'), Whose::theOperator());
    $keychain->keep(aStackSignedInto(A_MEMBERS_STACK), Session::of('a-session-not-a-secret'), Whose::member('robin'));

    app()->instance(SecureStorage::class, $keychain);

    return app()->make(WhichThemeIsOnTheGlass::class);
}

/** The theme a screen would be drawn in now, as the parser and the widgets read it. */
function whatIsPainted(): ?WhoseTheme
{
    TailwindParser::clearCache();

    foreach (WhoseTheme::cases() as $theme) {
        $surface = ThemeToken::Surface->in($theme);
        $parsed = TailwindParser::parse(sprintf('bg-theme-%s', ThemeToken::Surface->value));

        if ($parsed === ['bg' => $surface] && config('native-ui.theme.dark.background') === $surface) {
            return $theme;
        }
    }

    return null;
}

it('draws a screen for the operator\'s session in the operator\'s theme', function (): void {
    theThemeOnAPhoneWithSessions()->forTheScreen(AScreenComingToTheFront::about(aStackSignedInto(THE_OPERATORS_STACK)));

    expect(whatIsPainted())->toBe(WhoseTheme::Operator);
});

it('draws a screen for a member\'s session in the member\'s theme', function (): void {
    $theme = theThemeOnAPhoneWithSessions();
    $theme->paint(WhoseTheme::Operator);

    $theme->forTheScreen(AScreenComingToTheFront::about(aStackSignedInto(A_MEMBERS_STACK)));

    expect(whatIsPainted())->toBe(WhoseTheme::Member);
});

it('draws a screen about a stack this phone holds no session for in the member\'s theme', function (): void {
    $theme = theThemeOnAPhoneWithSessions();
    $theme->paint(WhoseTheme::Operator);

    $theme->forTheScreen(AScreenComingToTheFront::about(aStackSignedInto(NOBODYS_STACK)));

    expect(whatIsPainted())->toBe(WhoseTheme::Member);
});

it('draws a screen about no stack in the member\'s theme', function (): void {
    $theme = theThemeOnAPhoneWithSessions();
    $theme->paint(WhoseTheme::Operator);

    $theme->forTheScreen(AScreenComingToTheFront::aboutNoStack());

    expect(whatIsPainted())->toBe(WhoseTheme::Member);
});

it('draws a screen whose route names a stack by nothing but blanks in the member\'s theme', function (): void {
    $theme = theThemeOnAPhoneWithSessions();
    $theme->paint(WhoseTheme::Operator);

    $theme->forTheScreen(AScreenComingToTheFront::aboutABlank());

    expect(whatIsPainted())->toBe(WhoseTheme::Member);
});

it('draws a screen opened over another in the theme of the one beneath', function (string $beneath, WhoseTheme $painted): void {
    $theme = theThemeOnAPhoneWithSessions();
    $theme->forTheScreen(AScreenComingToTheFront::about(aStackSignedInto($beneath)));

    $theme->forTheScreen(new AScreenOpenedOverAnother());

    expect(whatIsPainted())->toBe($painted);
})->with([
    'over the operator\'s' => [THE_OPERATORS_STACK, WhoseTheme::Operator],
    'over a member\'s' => [A_MEMBERS_STACK, WhoseTheme::Member],
]);

it('draws a screen opened over nothing in the member\'s theme', function (): void {
    $theme = new TheTheme(static fn(): SecureStorage => AKeychainInMemory::working());

    expect($theme->whose())->toBe(WhoseTheme::Member);

    $theme->forTheScreen(new AScreenOpenedOverAnother());

    expect(whatIsPainted())->toBe(WhoseTheme::Member)
        ->and($theme->whose())->toBe(WhoseTheme::Member);
});

it('pushes the widgets\' colours only when the theme changes', function (): void {
    $theme = theThemeOnAPhoneWithSessions();
    $theme->forTheScreen(AScreenComingToTheFront::about(aStackSignedInto(THE_OPERATORS_STACK)));

    // Marked by hand: a second push for the same theme would overwrite it.
    config()->set('native-ui.theme.dark.background', '#00FF00');
    $theme->forTheScreen(AScreenComingToTheFront::about(aStackSignedInto(THE_OPERATORS_STACK)));

    expect(config('native-ui.theme.dark.background'))->toBe('#00FF00');

    $theme->forTheScreen(AScreenComingToTheFront::about(aStackSignedInto(A_MEMBERS_STACK)));

    expect(whatIsPainted())->toBe(WhoseTheme::Member);
});

it('hands each screen to the theme as it comes to the front, built or uncovered', function (): void {
    $handed = [];
    $built = AScreenComingToTheFront::aboutNoStack();
    $uncovered = AScreenComingToTheFront::about(aStackSignedInto(THE_OPERATORS_STACK));
    $router = new ScreenRouter(static fn(): AScreenComingToTheFront => $built, static function (NativeComponent $screen) use (&$handed): void {
        $handed[] = $screen;
    });

    theRouterBuilds($router, AScreenComingToTheFront::class);
    theRouterAnnounces($router, [], new ScreenResumed(AScreenComingToTheFront::class, '/'));
    theRouterAnnounces($router, [$uncovered], new ScreenMounted(AScreenComingToTheFront::class, '/'));
    theRouterAnnounces($router, [$uncovered], new ScreenResumed(AScreenComingToTheFront::class, '/'));

    expect($handed)->toBe([$built, $uncovered]);
});

/** `createComponent`, which is protected because only the router calls it. */
function theRouterBuilds(ScreenRouter $router, string $class): void
{
    new ReflectionMethod($router, 'createComponent')->invoke($router, $class);
}

/**
 * `announce`, with the navigation stack holding these screens, bottom first.
 *
 * @param list<NativeComponent> $screens
 */
function theRouterAnnounces(ScreenRouter $router, array $screens, object $event): void
{
    $stack = array_map(static fn(NativeComponent $screen): array => ['component' => $screen, 'uri' => '/', 'params' => []], $screens);

    new ReflectionProperty($router, 'stack')->setValue($router, $stack);
    new ReflectionMethod($router, 'announce')->invoke($router, $event);
}
