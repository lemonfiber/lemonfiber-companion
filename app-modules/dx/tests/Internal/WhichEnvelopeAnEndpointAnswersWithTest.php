<?php

declare(strict_types=1);

namespace Modules\Dx\Tests\Internal;

use function expect;
use function it;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Dx\Internal\WhichEnvelopeAnEndpointAnswersWith;

it('answers each value of a parameter with the envelope its sentence names', function (): void {
    expect(WhichEnvelopeAnEndpointAnswersWith::at(Api::UPDATE_ENDPOINT, 'self'))->toBe('SelfUpdateEnvelope')
        ->and(WhichEnvelopeAnEndpointAnswersWith::at(Api::UPDATE_ENDPOINT, 'stack'))->toBe('UpdateEnvelope');
});

it('answers with the envelope named first where no value has a sentence of its own', function (): void {
    expect(WhichEnvelopeAnEndpointAnswersWith::at(Api::UPDATE_ENDPOINT))->toBe('UpdateEnvelope')
        ->and(WhichEnvelopeAnEndpointAnswersWith::at(Api::UPDATE_ENDPOINT, 'a-word-nothing-quotes'))->toBe('UpdateEnvelope')
        ->and(WhichEnvelopeAnEndpointAnswersWith::at(Api::STATUS_ENDPOINT, 'self'))->toBe('StatusEnvelope');
});

it('answers nothing for a path nothing declares', function (): void {
    expect(WhichEnvelopeAnEndpointAnswersWith::at('/nowhere', 'self'))->toBe('');
});

it('names an envelope for every endpoint it lists', function (): void {
    expect(WhichEnvelopeAnEndpointAnswersWith::everyOneNamed())->not->toContain('');
});
