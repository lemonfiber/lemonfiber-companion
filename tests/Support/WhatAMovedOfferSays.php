<?php

declare(strict_types=1);

namespace Tests\Support;

use function json_encode;

use Lemonfiber\Sdk\Generated\RefusalCode;
use Saloon\Http\Faking\MockResponse;

/**
 * A stack refusing a yes because what it was given for has moved, as the wire carries it.
 *
 * One refusal for every port that carries an offer back, so each contract
 * reads the same words and the same code-and-status pairing the contract
 * declares, rather than a copy that could drift from it.
 */
final class WhatAMovedOfferSays
{
    public const string SUMMARY = 'What you agreed to is not what is offered now';

    public const string MEANING = 'The offer you answered was a1b2c3d4, and a fresh look offers e5f6a7b8.';

    /** The work, ended on a refusal carrying `$code`, in the stack's own words. */
    public static function endedOn(RefusalCode $code): MockResponse
    {
        return MockResponse::make((string) json_encode([
            'api_version' => 1,
            'kind' => 'error',
            'data' => [
                'code' => $code->value,
                'severity' => 'warning',
                'state' => 'guided',
                'summary' => self::SUMMARY,
                'meaning' => self::MEANING,
                'remedies' => [],
            ],
        ]), $code->status());
    }
}
