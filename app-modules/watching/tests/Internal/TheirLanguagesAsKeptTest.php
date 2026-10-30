<?php

declare(strict_types=1);

use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\TheirLanguages;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Whose;
use Modules\Watching\Internal\TheirLanguagesAsKept;

it('reads back what it wrote, for the member it was written for', function (): void {
    $chosen = TheirLanguages::of(HearIn::English, ReadIn::Dutch);
    $written = TheirLanguagesAsKept::written(Whose::member('ada'), $chosen);

    expect($written)->toBeInstanceOf(Unsealed::class)
        ->and(TheirLanguagesAsKept::read(Shape::One, $written ?? Unsealed::of(''), Whose::member('ada')))->toEqual($chosen);
});

it('hands nobody else the choice', function (): void {
    $written = TheirLanguagesAsKept::written(Whose::member('ada'), TheirLanguages::of(HearIn::English, ReadIn::Dutch)) ?? Unsealed::of('');

    expect(TheirLanguagesAsKept::read(Shape::One, $written, Whose::member('sam')))->toBeNull()
        ->and(TheirLanguagesAsKept::read(Shape::One, $written, Whose::theOperator()))->toBeNull();
});

it('reads nothing out of what does not read as a choice', function (string $written): void {
    expect(TheirLanguagesAsKept::read(Shape::One, Unsealed::of($written), Whose::member('ada')))->toBeNull();
})->with([
    'not json' => ['not json at all'],
    'not fields' => ['"ada"'],
    'no languages' => ['{"chosen_by":"ada"}'],
    'nothing to hear in' => ['{"chosen_by":"ada","reads_in":"none"}'],
    'nothing to read in' => ['{"chosen_by":"ada","hears_in":"nl"}'],
    'a language this build does not offer' => ['{"chosen_by":"ada","hears_in":"fr","reads_in":"none"}'],
    'a language that is not text' => ['{"chosen_by":"ada","hears_in":"nl","reads_in":3}'],
]);

it('reads the operator\'s own choice, and never one that names nobody as the operator\'s', function (): void {
    $theirs = TheirLanguagesAsKept::written(Whose::theOperator(), TheirLanguages::of(HearIn::Dutch, ReadIn::English)) ?? Unsealed::of('');

    expect(TheirLanguagesAsKept::read(Shape::One, $theirs, Whose::theOperator()))->toEqual(TheirLanguages::of(HearIn::Dutch, ReadIn::English))
        ->and(TheirLanguagesAsKept::read(Shape::One, Unsealed::of('{"hears_in":"nl","reads_in":"en"}'), Whose::theOperator()))->toBeNull();
});

it('cannot write a member whose identifier is not valid text', function (): void {
    expect(TheirLanguagesAsKept::written(Whose::member("\xB1\x31"), TheirLanguages::asTheTitleComes()))->toBeNull();
});
