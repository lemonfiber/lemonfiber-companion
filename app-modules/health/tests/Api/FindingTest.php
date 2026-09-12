<?php

declare(strict_types=1);

namespace Modules\Health\Tests\Api;

use function expect;
use function it;

use Modules\Health\Api\Category;
use Modules\Health\Api\Check;
use Modules\Health\Api\Conclusion;
use Modules\Health\Api\Finding;
use Modules\Health\Api\FindingHasNoTitle;

function aFinding(string $title = 'Torrent traffic leaves through the tunnel'): Finding
{
    return Finding::of(
        Check::of('vpn.egress-match'),
        Category::Vpn,
        $title,
        Conclusion::Passed,
    );
}

it('carries every field a row shows', function (): void {
    $finding = aFinding();

    expect($finding->check()->shown())->toBe('vpn.egress-match')
        ->and($finding->category())->toBe(Category::Vpn)
        ->and($finding->title())->toBe('Torrent traffic leaves through the tunnel')
        ->and($finding->conclusion())->toBe(Conclusion::Passed);
});

it('trims the title before carrying it', function (): void {
    expect(aFinding("  Torrent traffic leaves through the tunnel \n")->title())
        ->toBe('Torrent traffic leaves through the tunnel');
});

it('refuses a finding with no title', function (): void {
    expect(fn(): Finding => aFinding(''))->toThrow(FindingHasNoTitle::class);
});

it('refuses a title that is only whitespace', function (): void {
    expect(fn(): Finding => aFinding('   '))->toThrow(FindingHasNoTitle::class);
});

it('names the check when it refuses, because that is what a report is searched by', function (): void {
    expect(fn(): Finding => aFinding(''))
        ->toThrow(FindingHasNoTitle::class, 'vpn.egress-match arrived with no title');
});
