<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function is_string;

use Lemonfiber\Native\WhatTheOperatorSaid;
use Lemonfiber\Native\WhyNothingWasTold;

/**
 * What a handset's notification centre would answer, for a bridge with no handset.
 *
 * Scripted into `nativephp/mobile`'s `FakeBridge` so the adapter above it runs
 * the real call — the function name from the manifest, the JSON out, the JSON
 * back — rather than a parallel path built to resemble it.
 *
 * A class rather than a handful of closures over two variables, for the reason
 * {@see AHandsetsWindow} gives: closures capturing by reference start from a
 * literal and the analyser reads the first branch as permanently dead.
 *
 * **It counts.** Not asking a second time is not a question about a return
 * value, it is a question about how many times somebody was interrupted — so
 * the assertion that matters is a count, and something has to be keeping it.
 */
final class ANotificationCentreOnAHandset
{
    /** @var list<array{id: string, title: string, body: string}> */
    private array $shown = [];

    private int $prompts = 0;

    private function __construct(
        private WhatTheOperatorSaid $standing,
        private readonly ?WhyNothingWasTold $refusing,
    ) {}

    /** A device whose operator has allowed notifications. */
    public static function allowed(): self
    {
        return new self(WhatTheOperatorSaid::Granted, null);
    }

    /** A device whose operator has declined them. */
    public static function refused(): self
    {
        return new self(WhatTheOperatorSaid::Denied, null);
    }

    /** A device whose operator has not been asked. */
    public static function unasked(): self
    {
        return new self(WhatTheOperatorSaid::NotDetermined, null);
    }

    /**
     * A device that allows notifications and will not show one anyway.
     *
     * The state a boolean could not carry, and the whole reason the bridge
     * answers a word: a channel switched off and a platform that declined are
     * different sentences on a screen, and neither is a permission.
     */
    public static function allowedButRefusing(WhyNothingWasTold $because): self
    {
        return new self(WhatTheOperatorSaid::Granted, $because);
    }

    /**
     * What `Lemonfiber.Telling.Standing` answers.
     *
     * @return array{outcome: string}
     */
    public function standing(): array
    {
        return ['outcome' => $this->standing->value];
    }

    /**
     * What `Lemonfiber.Telling.Ask` answers.
     *
     * The prompt is recorded rather than raised, and the answer afterwards is a
     * grant — the case in which a second prompt would be least noticeable and
     * therefore most likely to survive review.
     *
     * It refuses to prompt twice, because a stand-in that re-asks happily is a
     * stand-in that lets a caller who prompts a second time pass every test it
     * appears in.
     *
     * @return array{outcome: string}
     */
    public function ask(): array
    {
        if ($this->standing->mayAsk()) {
            $this->prompts++;
            $this->standing = WhatTheOperatorSaid::Granted;
        }

        return $this->standing();
    }

    /**
     * What `Lemonfiber.Telling.Show` answers.
     *
     * @param array<string, mixed> $sent
     *
     * @return array{outcome: string, because?: string}
     */
    public function show(array $sent): array
    {
        if ($this->refusing instanceof WhyNothingWasTold) {
            return ['outcome' => 'withheld', 'because' => $this->refusing->value];
        }

        if (! $this->standing->mayProceed()) {
            return ['outcome' => 'withheld', 'because' => WhyNothingWasTold::NotPermitted->value];
        }

        $this->shown[] = [
            'id' => $this->said($sent, 'id'),
            'title' => $this->said($sent, 'title'),
            'body' => $this->said($sent, 'body'),
        ];

        return ['outcome' => 'shown'];
    }

    /**
     * What this centre was asked to show, in order.
     *
     * @return list<array{id: string, title: string, body: string}>
     */
    public function shown(): array
    {
        return $this->shown;
    }

    /** How many times the operator was interrupted. */
    public function prompts(): int
    {
        return $this->prompts;
    }

    /**
     * One field of what the bridge was sent.
     *
     * @param array<string, mixed> $sent
     */
    private function said(array $sent, string $key): string
    {
        return array_key_exists($key, $sent) && is_string($sent[$key]) ? $sent[$key] : '';
    }
}
