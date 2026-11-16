<?php

declare(strict_types=1);

use Modules\Kernel\Api\AnObstacleNamed;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Sentence;

it('carries the code, the sentence and the remedy it was named with, each where it belongs', function (): void {
    $obstacle = AnObstacleNamed::of(Code::of('LF-MEDIA-001'), Sentence::of('The media server did not answer.'), Remedy::of('Start the media server.'));

    expect($obstacle->code()->shown())->toBe('LF-MEDIA-001')
        ->and($obstacle->said()->shown())->toBe('The media server did not answer.')
        ->and($obstacle->remedy()->action())->toBe('Start the media server.');
});
