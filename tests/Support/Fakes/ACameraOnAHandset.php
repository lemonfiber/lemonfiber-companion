<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function array_key_exists;
use function is_string;

use Modules\Kernel\Api\WhyNothingWasScanned;

/**
 * What a handset's camera would answer, for a bridge with no handset.
 *
 * Scripted into `nativephp/mobile`'s `FakeBridge` so the adapter above it runs
 * the real call — the function name from the manifest, the JSON out, the JSON
 * back — rather than a parallel path built to resemble it.
 *
 * A class rather than a handful of closures over two variables, for the reason
 * {@see ANotificationCentreOnAHandset} gives: closures capturing by reference
 * start from a literal and the analyser reads the first branch as permanently
 * dead.
 *
 * **It keeps what it was told.** The sentence painted over the preview is the
 * application's own explanation, and on the first scan it is the last thing the
 * operator reads before the platform takes the screen. Nothing about a returned
 * value can show that it was carried, so this remembers it.
 */
final class ACameraOnAHandset
{
    /** @var list<string> */
    private array $prompts = [];

    private function __construct(
        private readonly ?string $payload,
        private readonly ?WhyNothingWasScanned $why,
        private readonly bool $mayAskAgain,
    ) {}

    /** A camera that reads the code named. */
    public static function reading(string $payload): self
    {
        return new self(payload: $payload, why: null, mayAskAgain: false);
    }

    /** A camera the operator backed out of. */
    public static function closed(): self
    {
        return new self(payload: null, why: WhyNothingWasScanned::TheOperatorClosedIt, mayAskAgain: true);
    }

    /**
     * A camera declined in the dialog, which the platform will still offer.
     *
     * The case the vendor's scanner cannot report and this one must: it is the
     * same refusal as the one below and the opposite advice.
     */
    public static function declined(): self
    {
        return new self(payload: null, why: WhyNothingWasScanned::TheCameraWasDeclined, mayAskAgain: true);
    }

    /** A camera turned off in settings, which nothing may ask about again. */
    public static function refusedForGood(): self
    {
        return new self(payload: null, why: WhyNothingWasScanned::TheCameraIsNotPermitted, mayAskAgain: false);
    }

    /** A device with no camera on it at all. */
    public static function absent(): self
    {
        return new self(payload: null, why: WhyNothingWasScanned::ThereIsNoCamera, mayAskAgain: false);
    }

    /**
     * What the bridge answers when the scanner is asked to read.
     *
     * @param  array<string, mixed>  $sent
     * @return array<string, bool|string>
     */
    public function read(array $sent): array
    {
        $prompt = array_key_exists('prompt', $sent) ? $sent['prompt'] : '';
        $this->prompts[] = is_string($prompt) ? $prompt : '';

        if (is_string($this->payload)) {
            return ['outcome' => 'read', 'payload' => $this->payload];
        }

        return [
            'outcome' => 'nothing',
            'because' => $this->refusalWord(),
            'may_ask_again' => $this->mayAskAgain,
        ];
    }

    /**
     * The sentences the operator was shown, in the order they were shown.
     *
     * @return list<string>
     */
    public function whatItSaid(): array
    {
        return $this->prompts;
    }

    /**
     * The bridge's own word for the reason a test named.
     *
     * Written out rather than derived from the adapter's translation. Deriving
     * it would mean the stand-in and the adapter sharing one mapping, and a
     * mapping that was wrong in both directions would agree with itself and
     * pass.
     */
    private function refusalWord(): string
    {
        return match ($this->why) {
            WhyNothingWasScanned::ThereIsNoCamera => 'there_is_no_camera',
            WhyNothingWasScanned::TheCameraIsNotPermitted,
            WhyNothingWasScanned::TheCameraWasDeclined => 'the_camera_is_not_permitted',
            default => 'the_operator_closed_it',
        };
    }
}
