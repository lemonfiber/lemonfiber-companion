<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ABundleFetched;
use Modules\Kernel\Api\ABundleFile;
use Modules\Kernel\Api\AWrittenBundle;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Tests\Support\TheWordCarriedOut;

/** Which arm a bundle fetched takes, and what it carried there. */
function whatTheFetchCameTo(ABundleFetched $fetched): string
{
    return $fetched->either(
        fetched: static fn(ABundleFile $file): TheWordCarriedOut => new TheWordCarriedOut($file->named()),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->name),
    )->said;
}

it('takes the arm for the file it fetched, or for the obstacle it met', function (): void {
    $file = ABundleFile::fetched(AWrittenBundle::at('/home/op/bundles/lemonfiber-support.tar.gz'), 'a');

    expect(whatTheFetchCameTo(ABundleFetched::as($file)))->toBe('lemonfiber-support.tar.gz')
        ->and(whatTheFetchCameTo(ABundleFetched::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('StackDidNotAnswer');
});
