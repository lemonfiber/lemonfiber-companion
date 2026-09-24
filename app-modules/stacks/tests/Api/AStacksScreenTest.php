<?php

declare(strict_types=1);

namespace Modules\Stacks\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AScreenNeedsMoreThanAStack;
use Modules\Stacks\Api\AStacksScreen;

use function sprintf;
use function str_contains;

/**
 * The paths themselves, asserted where the enum is rather than through a screen.
 *
 * Every other test of these goes through a builder or a router, which proves
 * that the two agree and not that either is right: a builder putting the
 * machine in the service's segment and the service in the machine's would
 * resolve — each pattern matches any single segment — and open somebody else's
 * service. What is asserted here is which segment each value lands in.
 */

/** A stack identifier and a service, spelled so neither could be read as the other. */
const A_MACHINE = 'the-machine';

const A_SERVICE = 'the-service';

it('puts the machine in the machine\'s segment and nothing else', function (): void {
    expect(AStacksScreen::Health->forTheStack(StackId::rememberedAs(A_MACHINE)))
        ->toBe(sprintf('/stacks/%s', A_MACHINE))
        ->and(AStacksScreen::Owed->forTheStack(StackId::rememberedAs(A_MACHINE)))
        ->toBe(sprintf('/stacks/%s/yours', A_MACHINE));
});

it('puts the machine and the service each in their own segment', function (): void {
    // Both, and in that order. A builder that substituted one and left the
    // other would hand back a path with a placeholder still in it, which
    // resolves to nothing; one that swapped them resolves and opens the wrong
    // thing.
    expect(AStacksScreen::Logs->forTheStacksService(
        StackId::rememberedAs(A_MACHINE),
        ServiceId::called(A_SERVICE),
    ))->toBe(sprintf('/stacks/%s/logs/%s', A_MACHINE, A_SERVICE));

    expect(AStacksScreen::Doing->forTheStacksService(
        StackId::rememberedAs(A_MACHINE),
        ServiceId::called(A_SERVICE),
    ))->toBe(sprintf('/stacks/%s/do/%s', A_MACHINE, A_SERVICE));
});

it('puts a whole form where a service would go, and the machine where it belongs', function (): void {
    // The same segment filled by a different thing, which is why it is a second
    // builder. A form and a service are both text, and a single builder taking
    // one string for either is the mistake that compiles.
    expect(AStacksScreen::Doing->forTheStacksForm(
        StackId::rememberedAs(A_MACHINE),
        Form::called('arr'),
    ))->toBe(sprintf('/stacks/%s/do/arr', A_MACHINE));
});

it('puts one of lemonfiber\'s words where a service would go', function (): void {
    expect(AStacksScreen::WordAbout->forTheStacksWord(
        StackId::rememberedAs(A_MACHINE),
        AWordInUse::named('ratio'),
    ))->toBe(sprintf('/stacks/%s/words/ratio', A_MACHINE));
});

it('leaves no placeholder in anything it hands out', function (): void {
    // The failure every one of these exists to prevent, asserted over the whole
    // enum rather than case by case: a path still carrying `{stack}` or
    // `{service}` resolves to nothing, and a control pointing at it does
    // nothing on a handset with no error anywhere.
    foreach (AStacksScreen::cases() as $screen) {
        $path = $screen->alsoNeedsAService()
            ? $screen->forTheStacksService(StackId::rememberedAs(A_MACHINE), ServiceId::called(A_SERVICE))
            : $screen->forTheStack(StackId::rememberedAs(A_MACHINE));

        expect(str_contains($path, AStacksScreen::NAMED))->toBeFalse($screen->name)
            ->and(str_contains($path, AStacksScreen::ABOUT))->toBeFalse($screen->name);
    }
});

it('refuses a case that needs more than a machine, and one that needs less', function (): void {
    expect(fn(): string => AStacksScreen::Logs->forTheStack(StackId::rememberedAs(A_MACHINE)))
        ->toThrow(AScreenNeedsMoreThanAStack::class, 'naming a machine is not enough');

    expect(fn(): string => AStacksScreen::Health->forTheStacksService(
        StackId::rememberedAs(A_MACHINE),
        ServiceId::called(A_SERVICE),
    ))->toThrow(AScreenNeedsMoreThanAStack::class, 'names no service');

    expect(fn(): string => AStacksScreen::Health->forTheStacksForm(
        StackId::rememberedAs(A_MACHINE),
        Form::called('arr'),
    ))->toThrow(AScreenNeedsMoreThanAStack::class, 'names no service');

    expect(fn(): string => AStacksScreen::Health->forTheStacksWord(
        StackId::rememberedAs(A_MACHINE),
        AWordInUse::named('ratio'),
    ))->toThrow(AScreenNeedsMoreThanAStack::class, 'names no service');
});
