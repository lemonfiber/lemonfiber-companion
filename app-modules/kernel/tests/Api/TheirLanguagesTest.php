<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\TheirLanguages;

it('is the title as it comes where nothing was chosen: its own sound, and no subtitles', function (): void {
    $chosen = TheirLanguages::asTheTitleComes();

    expect($chosen->hear())->toBe(HearIn::TheOriginal)
        ->and($chosen->read())->toBe(ReadIn::Nothing);
});

it('changes one language and keeps the other', function (): void {
    $chosen = TheirLanguages::of(HearIn::Dutch, ReadIn::English);

    expect($chosen->hearing(HearIn::English))->toEqual(TheirLanguages::of(HearIn::English, ReadIn::English))
        ->and($chosen->reading(ReadIn::Nothing))->toEqual(TheirLanguages::of(HearIn::Dutch, ReadIn::Nothing));
});
