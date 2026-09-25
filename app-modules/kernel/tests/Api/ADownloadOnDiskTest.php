<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\ARatio;
use Modules\Kernel\Api\RoomSaysNothing;
use Modules\Kernel\Api\WhereADownloadStands;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheDownloadTook
{
    public function __construct(public string $said) {}
}

/** Whether a download carries a ratio. */
function whatRatioItCarries(ADownloadOnDisk $download): string
{
    return $download->ratio(
        seeding: static fn(ARatio $ratio): WhichArmTheDownloadTook => $ratio->either(
            read: static fn(string $read): WhichArmTheDownloadTook => new WhichArmTheDownloadTook(sprintf('seeding %s', $read)),
            none: static fn(): WhichArmTheDownloadTook => new WhichArmTheDownloadTook('seeding, no ratio'),
        ),
        notSeeding: static fn(): WhichArmTheDownloadTook => new WhichArmTheDownloadTook('none'),
    )->said;
}

it('N12-R1, N12-R2 — each standing is built on its own, and only seeding carries a ratio', function (): void {
    $never = ADownloadOnDisk::neverImported('a', 1);
    $seeding = ADownloadOnDisk::seeding('b', 2, ARatio::inHundredths(10));
    $alone = ADownloadOnDisk::leftAlone('c', 0);

    expect([$never->name(), $never->bytes(), $never->stands(), whatRatioItCarries($never)])->toBe(['a', 1, WhereADownloadStands::NeverImported, 'none'])
        ->and([$seeding->stands(), whatRatioItCarries($seeding)])->toBe([WhereADownloadStands::Seeding, 'seeding 0.10'])
        ->and([$alone->stands(), $alone->bytes(), whatRatioItCarries($alone)])->toBe([WhereADownloadStands::LeftAlone, 0, 'none']);
});

it('N12-R3 — carries what removing it costs, where the stack says, and nothing otherwise', function (): void {
    expect(ADownloadOnDisk::neverImported('a', 1)->consequence())->toBe('')
        ->and(ADownloadOnDisk::neverImported('a', 1, 'Nothing is lost')->consequence())->toBe('Nothing is lost')
        ->and(ADownloadOnDisk::seeding('b', 2, ARatio::none(), 'Your ratio stops growing')->consequence())->toBe('Your ratio stops growing')
        ->and(ADownloadOnDisk::leftAlone('c', 3, 'You asked for this')->consequence())->toBe('You asked for this');
});

it('refuses a blank name, a size below nothing, and a blank cost', function (): void {
    expect(fn(): ADownloadOnDisk => ADownloadOnDisk::neverImported(' ', 1))->toThrow(RoomSaysNothing::class, '`name`')
        ->and(fn(): ADownloadOnDisk => ADownloadOnDisk::leftAlone('a', -1))->toThrow(RoomSaysNothing::class, '`bytes`')
        ->and(fn(): ADownloadOnDisk => ADownloadOnDisk::neverImported('a', 1, ' '))->toThrow(RoomSaysNothing::class, '`consequence`');
});
