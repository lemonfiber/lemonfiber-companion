<?php

declare(strict_types=1);

use Lemonfiber\Native\Player\OfferedTrack;

it('reads every track in the list, in order', function (): void {
    expect(OfferedTrack::listedIn([
        ['id' => '0:0', 'language' => 'en', 'label' => 'English'],
        ['id' => '0:1', 'language' => '', 'label' => 'Commentary'],
    ]))->toEqual([new OfferedTrack('0:0', 'en', 'English'), new OfferedTrack('0:1', '', 'Commentary')]);
});

it('leaves out anything in the list that is not a track', function (): void {
    expect(OfferedTrack::listedIn([
        'a word',
        ['language' => 'en', 'label' => 'English'],
        ['id' => '0:1', 'label' => 'English'],
        ['id' => '0:2', 'language' => 'en'],
        ['id' => 3, 'language' => 'en', 'label' => 'English'],
        ['id' => '0:4', 'language' => 4, 'label' => 'English'],
        ['id' => '0:5', 'language' => 'en', 'label' => 5],
        ['id' => '0:6', 'language' => 'de', 'label' => 'Deutsch'],
    ]))->toEqual([new OfferedTrack('0:6', 'de', 'Deutsch')]);
});

it('reads no list as no tracks', function (): void {
    expect(OfferedTrack::listedIn(null))->toBe([]);
});
