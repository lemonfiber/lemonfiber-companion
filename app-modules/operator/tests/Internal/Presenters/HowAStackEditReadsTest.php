<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\AStackEdit;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Operator\Internal\Presenters\HowAStackEditReads;
use Modules\Operator\Internal\ViewModels\ADiffLineAsShown;
use Modules\Operator\Internal\ViewModels\AnEditAsShown;

it('marks every line of a file as the operator\'s or lemonfiber\'s, in the stack\'s order', function (): void {
    $edits = TheStackEdits::these(
        AStackEdit::at('compose.yaml', "- image: mine\n+ image: ours\n"),
        AStackEdit::at('env/sonarr.env', ''),
    );

    expect(new HowAStackEditReads()->these($edits))->toEqual([
        new AnEditAsShown('compose.yaml', [
            new ADiffLineAsShown(HowAStackEditReads::THEIRS, 'image: mine'),
            new ADiffLineAsShown(HowAStackEditReads::LEMONFIBERS, 'image: ours'),
        ]),
        new AnEditAsShown('env/sonarr.env', []),
    ]);
});

it('shows nothing where no file was edited', function (): void {
    expect(new HowAStackEditReads()->these(TheStackEdits::none()))->toBe([]);
});
