<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAnswerOutOfContract;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\WhatVouchesForAPlugin;

use function sprintf;

use Tests\Support\APluginAsItArrives;

it('keeps what the stack said of an answer, and owns it only for the plugin whose adapter gave it', function (): void {
    $answer = AnAnswerOutOfContract::of('tdarr', 'transcoding', 'queue', 'It answered with a field its contract does not have');
    $another = APlugin::named('komga', '1.0.0', 'Komga', WhatVouchesForAPlugin::recorded('', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these());

    expect([$answer->capability(), $answer->operation(), $answer->why()])->toBe(['transcoding', 'queue', 'It answered with a field its contract does not have'])
        ->and($answer->isOf(APluginAsItArrives::held()))->toBeTrue()
        ->and($answer->isOf($another))->toBeFalse();
});

it('refuses an answer with any of its words left blank, naming the one', function (string $field, string $plugin, string $capability, string $operation, string $why): void {
    expect(static fn(): AnAnswerOutOfContract => AnAnswerOutOfContract::of($plugin, $capability, $operation, $why))
        ->toThrow(PluginSaysNothing::class, sprintf('`%s`', $field));
})->with([
    'no plugin' => ['plugin', ' ', 'transcoding', 'queue', 'why'],
    'no capability' => ['capability', 'tdarr', '', 'queue', 'why'],
    'no operation' => ['operation', 'tdarr', 'transcoding', "\t", 'why'],
    'no reason' => ['why', 'tdarr', 'transcoding', 'queue', ' '],
]);

it('narrows the answers to the ones a plugin gave, in the stack\'s order', function (): void {
    $answers = TheAnswersOutOfContract::these(
        AnAnswerOutOfContract::of('tdarr', 'transcoding', 'queue', 'first'),
        AnAnswerOutOfContract::of('komga', 'reading', 'list', 'other'),
        AnAnswerOutOfContract::of('tdarr', 'transcoding', 'cancel', 'second'),
    );

    expect(array_map(
        static fn(AnAnswerOutOfContract $answer): string => $answer->why(),
        iterator_to_array($answers->of(APluginAsItArrives::held()), preserve_keys: false),
    ))->toBe(['first', 'second']);
});
