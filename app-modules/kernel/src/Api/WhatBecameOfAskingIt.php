<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;
use function trim;

/**
 * What became of asking a service to act on a quality choice, or that it was not asked.
 *
 * Nothing is asked of a service until the operator says yes, so a described
 * upgrade and a rehearsed choice carry {@see self::notAsked()} — told apart
 * from a service that was asked and did not start.
 */
final readonly class WhatBecameOfAskingIt
{
    /** The catalogue word for a service nothing was asked of. */
    private const string NOT_ASKED = 'not-asked';

    private function __construct(private string $word, private string $detail) {}

    /** Nothing was asked of the service. */
    public static function notAsked(): self
    {
        return new self(self::NOT_ASKED, '');
    }

    /** The service was asked, and it started or was not ready. */
    public static function asked(WhereTheAskingStands $stands): self
    {
        if ($stands === WhereTheAskingStands::Failed) {
            throw QualitySaysNothing::about('detail');
        }

        return new self($stands->value, '');
    }

    /** The service was asked and refused, or could not be reached, in its own words. */
    public static function failed(string $detail): self
    {
        if (trim($detail) === '') {
            throw QualitySaysNothing::about('detail');
        }

        return new self(WhereTheAskingStands::Failed->value, $detail);
    }

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('quality.asked.%s', $this->word);
    }

    /** The service's own account of why it failed, or empty. */
    public function detail(): string
    {
        return $this->detail;
    }
}
