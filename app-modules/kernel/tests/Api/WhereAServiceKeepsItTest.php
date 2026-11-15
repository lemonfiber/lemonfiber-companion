<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\TheSurveySaysNothing;
use Modules\Kernel\Api\WhereAServiceKeepsIt;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm a place took, and the words it carries. */
function theArmTaken(WhereAServiceKeepsIt $place): string
{
    return $place->either(
        at: static fn(string $path): TheWordCarriedOut => new TheWordCarriedOut(sprintf('at:%s', $path)),
        unread: static fn(string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('unread:%s', $why)),
        none: static fn(string $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('none:%s', $why)),
    )->said;
}

it('keeps a host path, a place that could not be read and a service keeping none apart', function (): void {
    expect(theArmTaken(WhereAServiceKeepsIt::at('/srv/sonarr/config')))->toBe('at:/srv/sonarr/config')
        ->and(theArmTaken(WhereAServiceKeepsIt::unread('It is mounted from a volume')))->toBe('unread:It is mounted from a volume')
        ->and(theArmTaken(WhereAServiceKeepsIt::none('It keeps no library')))->toBe('none:It keeps no library');
});

it('refuses a blank path or reason, naming which', function (): void {
    expect(static fn(): WhereAServiceKeepsIt => WhereAServiceKeepsIt::at(' '))->toThrow(TheSurveySaysNothing::class, '`at`')
        ->and(static fn(): WhereAServiceKeepsIt => WhereAServiceKeepsIt::unread(''))->toThrow(TheSurveySaysNothing::class, '`unread`')
        ->and(static fn(): WhereAServiceKeepsIt => WhereAServiceKeepsIt::none("\t"))->toThrow(TheSurveySaysNothing::class, '`none`');
});
