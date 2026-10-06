<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;

it('is asked for by the name lemonfiber gives the action, before and after there is one', function (): void {
    expect(AGuardAskedFor::of(Forms::these(Form::called('library')))->asked())->toBe('watch')
        ->and(AGuardAskedFor::named())->toBe('watch');
});

it('guards the forms it was asked for', function (): void {
    $forms = Forms::these(Form::called('library'), Form::called('full'));

    expect(AGuardAskedFor::of($forms)->forms())->toBe($forms);
});
