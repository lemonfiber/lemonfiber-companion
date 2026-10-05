<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Tests\Api;

use function expect;
use function it;

use Modules\Design\Api\WhoseTheme;
use Modules\Wayfinding\Api\WhoTheMenuIsFor;

it('draws a screen in the operator\'s theme for the operator\'s session, and in the member\'s for a member\'s or for nobody\'s', function (WhoTheMenuIsFor $whose, WhoseTheme $theme): void {
    expect($whose->theme())->toBe($theme);
})->with([
    'the operator' => [WhoTheMenuIsFor::TheOperator, WhoseTheme::Operator],
    'a member' => [WhoTheMenuIsFor::AMember, WhoseTheme::Member],
    'nobody signed in' => [WhoTheMenuIsFor::Anyone, WhoseTheme::Member],
]);
