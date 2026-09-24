<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_keys;
use function expect;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\Forms;
use Modules\Sdk\Api\RepertoireIsUnreadable;
use Modules\Sdk\Api\Repertoires;
use Tests\Support\WhatTheContractAccepts;

/**
 * A `forms` envelope holding whatever the case under test is about.
 *
 * Built by hand rather than fetched, for {@see aRosterSaying()}'s reason: what
 * is tested is what happens when the wire says something the contract does not
 * allow, which a client honouring the contract could not produce.
 *
 * @return Envelope<mixed>
 */
function aRepertoireSaying(mixed $data): Envelope
{
    return new Envelope(1, 'forms', $data);
}

/**
 * One form as a stack lists it, with whatever this case is about changed.
 *
 * Complete, `name`, `description` and `composable` included, which nothing
 * here reads: a fixture short of a required field is a sample of a payload no
 * stack sends.
 *
 * @param  array<mixed> $differently
 * @return array<mixed>
 */
function aFormSaying(string $id, array $differently = []): array
{
    return [...[
        'id' => $id,
        'name' => 'Library',
        'description' => 'Serve what exists. Requires no third-party accounts.',
        'composable' => true,
    ], ...$differently];
}

/**
 * The names of the forms a reading came to, in its order.
 *
 * @return list<string>
 */
function theFormsNamedIn(Forms $forms): array
{
    $named = [];

    foreach ($forms as $form) {
        $named[] = $form->named();
    }

    return $named;
}

it('N2-R7 — reads every form the stack declares, by the id a verb asks for it by', function (): void {
    // The id and not the name: `library` is what `up` is told, and `Library`
    // is a label a stack of somebody's own may spell however it likes.
    $forms = Repertoires::in(aRepertoireSaying(['forms' => [
        aFormSaying('library'),
        aFormSaying('full', ['name' => 'Full', 'description' => 'The lot.']),
        aFormSaying('proxy', ['name' => 'Proxy', 'composable' => false]),
    ]]));

    expect(theFormsNamedIn($forms))->toBe(['library', 'full', 'proxy'])
        ->and(array_keys(iterator_to_array($forms, preserve_keys: true)))->toBe([0, 1, 2]);
});

it('reads a stack that declares no forms as none', function (): void {
    expect(Repertoires::in(aRepertoireSaying(['forms' => []]))->count())->toBe(0);
});

it('refuses a payload that is not a shape at all', function (): void {
    expect(fn(): Forms => Repertoires::in(aRepertoireSaying('a sentence where a payload belongs')))
        ->toThrow(RepertoireIsUnreadable::class, '`data`');
});

it('refuses a payload with no list of forms, or one that is not a list', function (): void {
    // Absent and present-but-not-a-list are both refused by the field, so a
    // stack that sent something odd is not read as one that declares nothing.
    foreach (['absent' => [], 'a word' => ['forms' => 'library']] as $which => $data) {
        expect(fn(): Forms => Repertoires::in(aRepertoireSaying($data)))
            ->toThrow(RepertoireIsUnreadable::class, '`forms`', $which);
    }
});

it('refuses an entry that is not a form, and says which', function (): void {
    expect(fn(): Forms => Repertoires::in(aRepertoireSaying(['forms' => [aFormSaying('library'), 'full']])))
        ->toThrow(RepertoireIsUnreadable::class, 'Form 1 in the forms envelope is not a form');
});

it('refuses a form with no id a verb could ask for it by', function (): void {
    // Missing, not text, and only spacing: three ways to a control that could
    // only send a blank, each refused at the position it arrived in.
    $row = aFormSaying('full');
    unset($row['id']);

    $each = [
        'missing' => $row,
        'a number' => aFormSaying('full', ['id' => 7]),
        'only spacing' => aFormSaying('full', ['id' => "  \t"]),
    ];

    foreach ($each as $which => $said) {
        expect(fn(): Forms => Repertoires::in(aRepertoireSaying(['forms' => [aFormSaying('library'), $said]])))
            ->toThrow(RepertoireIsUnreadable::class, 'Form 1 in the forms envelope has no readable `id`', $which);
    }
});

it('refuses an answer in a wire version this app does not read', function (): void {
    expect(fn(): Forms => Repertoires::in(new Envelope(99, 'forms', ['forms' => []])))
        ->toThrow(EnvelopeIsNotRead::class);
});

it('stands in for a stack with a payload the contract would accept', function (): void {
    $payload = ['api_version' => 1, 'kind' => 'forms', 'data' => ['forms' => [
        aFormSaying('library'),
        aFormSaying('proxy', ['composable' => false]),
    ]]];

    expect(WhatTheContractAccepts::complaintsAbout('FormsEnvelope', $payload))->toBe(
        [],
        "The payload this suite stands in for a stack with is not one a stack would send.\n",
    );
});
