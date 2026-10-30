<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;

use InvalidArgumentException;

use function it;

use Modules\Sdk\Api\WireField;
use Modules\Sdk\Internal\Required;

it('reads text with something in it as it arrived, and refuses text that is absent, not text, empty or only spaces', function (): void {
    $refused = new InvalidArgumentException('refused');

    expect(Required::text(['name' => ' Dune '], WireField::Name, $refused))->toBe(' Dune ');

    foreach ([[], ['name' => 3], ['name' => ''], ['name' => '   ']] as $table) {
        expect(static fn(): string => Required::text($table, WireField::Name, $refused))->toThrow($refused);
    }
});

it('takes only text with something in it for text', function (): void {
    expect(Required::isText(' Dune '))->toBeTrue()
        ->and(Required::isText('   '))->toBeFalse()
        ->and(Required::isText(''))->toBeFalse()
        ->and(Required::isText(3))->toBeFalse();
});
