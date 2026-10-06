<?php

declare(strict_types=1);

use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Operator\Internal\ViewModels\AnOffer;
use Tests\Support\WhatMarkupDraws;
use Tests\TestCase;

// A render needs the view factory, the component namespace and the precompiler,
// so the application is booted.
uses(TestCase::class);

/** The outline of one offered button, drawn with the stack having said this. */
function anOfferedButtonDrawn(WhetherItIsOffered $whether, string $drawn = 'button', bool $disabled = false): string
{
    return WhatMarkupDraws::outline(
        '<native:column><x-operator::offered-action label="Restart" tap="restart()" :offer="$offer" :drawn="$drawn" :disabled="$disabled" /></native:column>',
        ['offer' => AnOffer::of($whether, '/stacks/a/updates'), 'drawn' => $drawn, 'disabled' => $disabled],
    );
}

it('draws a button the stack offers as the button it is, and says nothing beside it', function (WhetherItIsOffered $whether): void {
    expect(anOfferedButtonDrawn($whether))->toBe('column[][button{"width":"fill"}[]]');
})->with([
    'offered' => [WhetherItIsOffered::Offered],
    'not known' => [WhetherItIsOffered::NotKnown],
]);

it('draws one the stack has and has not set up, and says what is missing beside it', function (): void {
    expect(anOfferedButtonDrawn(WhetherItIsOffered::NotSetUp))
        ->toBe(sprintf('column[][button{"width":"fill"}[], %s]', WhatMarkupDraws::words('connection.not_set_up')));
});

it('draws one the account may not use, and says it is not theirs', function (): void {
    expect(anOfferedButtonDrawn(WhetherItIsOffered::NotTheirs))
        ->toBe(sprintf('column[][button{"width":"fill"}[], %s]', WhatMarkupDraws::words('connection.not_for_this_account')));
});

it('draws one the stack is too old for, says so, and gives the road to its updates', function (): void {
    expect(anOfferedButtonDrawn(WhetherItIsOffered::NeedsANewerLemonfiber))
        ->toBe(sprintf(
            'column[][button{"width":"fill"}[], %s, pressable{"width":"fill","min_height":48,"padding":[8,0,8,0],"justify_content":1}[%s]]',
            WhatMarkupDraws::words('connection.not_on_this_stack'),
            WhatMarkupDraws::words('connection.go_to_updates'),
        ));
});

it('presses only a button the stack offers and the screen has not held back', function (WhetherItIsOffered $whether, bool $disabled, bool $pressable): void {
    $button = WhatMarkupDraws::drawn(
        '<native:column><x-operator::offered-action label="Restart" tap="restart()" :offer="$offer" :disabled="$disabled" /></native:column>',
        ['offer' => AnOffer::of($whether, '/stacks/a/updates'), 'disabled' => $disabled],
    );

    expect((bool) data_get($button, 'children.0.props.disabled'))->toBe(! $pressable);
})->with([
    'offered' => [WhetherItIsOffered::Offered, false, true],
    'offered, and held back by the screen' => [WhetherItIsOffered::Offered, true, false],
    'not theirs' => [WhetherItIsOffered::NotTheirs, false, false],
    'too old' => [WhetherItIsOffered::NeedsANewerLemonfiber, false, false],
]);

it('keeps the quiet look and the leading look only while it can be pressed', function (string $drawn): void {
    expect(anOfferedButtonDrawn(WhetherItIsOffered::Offered, $drawn))->not->toContain('button')
        ->and(anOfferedButtonDrawn(WhetherItIsOffered::NeedsANewerLemonfiber, $drawn))->toContain('button');
})->with(['quiet' => ['quiet'], 'link' => ['link']]);
