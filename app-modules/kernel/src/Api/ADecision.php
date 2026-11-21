<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One answer setup writes, as the stack lists it: never with its value. */
final readonly class ADecision
{
    private function __construct(
        public string $key,
        public Cost $cost,
        public string $affects,
        public bool $held,
        public bool $credential,
        public ?string $setting,
    ) {}

    public static function inTheConfiguration(string $key, Cost $cost, string $affects, bool $held, bool $credential): self
    {
        return new self(self::said($key, 'key'), $cost, self::said($affects, 'affects'), $held, $credential, null);
    }

    public static function inTheSettings(string $key, Cost $cost, string $affects, bool $held, bool $credential, string $setting): self
    {
        return new self(self::said($key, 'key'), $cost, self::said($affects, 'affects'), $held, $credential, self::said($setting, 'setting'));
    }

    private static function said(string $text, string $part): string
    {
        $said = trim($text);

        if ($said === '') {
            throw ADecisionSaysNothing::about($part);
        }

        return $said;
    }
}
