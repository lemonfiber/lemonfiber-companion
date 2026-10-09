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
    'a language this build does not offer' => ['{"chosen_by":"ada","hears_in":"fr","reads_in":"none"}'],
    'a language that is not text' => ['{"chosen_by":"ada","hears_in":"nl","reads_in":3}'],
]);

it('cannot write a member whose identifier is not valid text', function (): void {
    expect(TheirLanguagesAsKept::written(Whose::member("\xB1\x31"), TheirLanguages::asTheTitleComes()))->toBeNull();
});
