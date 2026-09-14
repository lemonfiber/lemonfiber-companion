<?php

declare(strict_types=1);

use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\FormIsUnnamed;
use Modules\Kernel\Api\Forms;

it('N2-R7 — carries the name exactly as the stack spells it', function (): void {
    expect(Form::called('media')->named())->toBe('media');
});

it('keeps the name, less the whitespace around it', function (): void {
    expect(Form::called("  network\n")->named())->toBe('network');
});

it('refuses a form named as nothing at all', function (): void {
    // A heading with nothing in it is a group an operator is invited to stop
    // without being told what is in it.
    expect(fn(): Form => Form::called('   '))
        ->toThrow(FormIsUnnamed::class, 'nothing at all');
});

it('says whether two names are the same form', function (): void {
    expect(Form::called('media')->isTheSameAs(Form::called('media')))->toBeTrue()
        ->and(Form::called('media')->isTheSameAs(Form::called('network')))->toBeFalse()
        ->and(Form::called(' media ')->isTheSameAs(Form::called('media')))->toBeTrue();
});

it('holds the forms a stack declared, in the order it declared them', function (): void {
    $named = [];

    foreach (Forms::these(Form::called('media'), Form::called('network')) as $form) {
        $named[] = $form->named();
    }

    expect($named)->toBe(['media', 'network'])
        ->and(Forms::none()->count())->toBe(0);
});

it('reads by position, whatever keys the variadic arrived with', function (): void {
    // Named arguments give a variadic string keys, and this collection
    // hands its items out again through its iterator — so the keys escape,
    // and everything downstream reads by position. The keys are what has to
    // be read back: `foreach` yields insertion order whatever they are, so
    // a collection that had kept `first` and `second` iterates identically
    // to one that reindexed, and an assertion over the names a `foreach`
    // collects is an assertion about its own accumulator. `Scrollback` and
    // `Stalled` read the keys for the same reason.
    $forms = Forms::these(first: Form::called('media'), second: Form::called('network'));

    expect(array_keys(iterator_to_array($forms, preserve_keys: true)))->toBe([0, 1]);
});
