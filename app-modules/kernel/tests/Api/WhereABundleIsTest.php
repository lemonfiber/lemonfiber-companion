<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhereABundleIs;

use function sprintf;

/** One line carried out of an arm of where a bundle is. */
final readonly class WhichArmTheBundlesPlaceTook
{
    public function __construct(public string $said) {}
}

/** Which arm where a bundle is takes, and the path it carried there. */
function whereTheBundleIsReads(WhereABundleIs $where): string
{
    return $where->either(
        wouldGo: static fn(string $path): WhichArmTheBundlesPlaceTook => new WhichArmTheBundlesPlaceTook(sprintf('would go:%s', $path)),
        written: static fn(string $path): WhichArmTheBundlesPlaceTook => new WhichArmTheBundlesPlaceTook(sprintf('written:%s', $path)),
        unsaid: static fn(): WhichArmTheBundlesPlaceTook => new WhichArmTheBundlesPlaceTook('unsaid'),
    )->said;
}

it('takes the arm for each place, carrying the path, and says which of them is written', function (): void {
    $path = '/home/op/.config/lemonfiber/bundles/lemonfiber-support-2026-09-26T10-00-00Z.tar.gz';

    expect(whereTheBundleIsReads(WhereABundleIs::wouldGo($path)))->toBe(sprintf('would go:%s', $path))
        ->and(whereTheBundleIsReads(WhereABundleIs::writtenAt($path)))->toBe(sprintf('written:%s', $path))
        ->and(whereTheBundleIsReads(WhereABundleIs::unsaid()))->toBe('unsaid')
        ->and(WhereABundleIs::wouldGo($path)->isWritten())->toBeFalse()
        ->and(WhereABundleIs::writtenAt($path)->isWritten())->toBeTrue()
        ->and(WhereABundleIs::unsaid()->isWritten())->toBeFalse();
});
