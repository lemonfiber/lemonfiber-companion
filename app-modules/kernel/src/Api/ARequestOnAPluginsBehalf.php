<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A request lemonfiber makes on an installed plugin's behalf, attributed to the plugin that declared it.
 *
 * Neither lemonfiber's own, which it has a reason of its own to make, nor a
 * service's, which no service made: the third kind, kept apart from both. Like
 * lemonfiber's own, it says whether this machine allows it, the setting that
 * refuses it and what stops working once it is refused.
 */
final readonly class ARequestOnAPluginsBehalf
{
    private function __construct(
        private APluginId $plugin,
        private string $destination,
        private string $purpose,
        private WhetherItIsAllowed $allowed,
        private string $switch,
        private string $cost,
    ) {}

    /** What a plugin declared, and what this machine makes of it; a blank word is refused. */
    public static function declaredBy(
        APluginId $plugin,
        string $destination,
        string $purpose,
        WhetherItIsAllowed $allowed,
        string $switch,
        string $cost,
    ): self {
        return new self(
            $plugin,
            self::said('destination', $destination),
            self::said('purpose', $purpose),
            $allowed,
            self::said('switch', $switch),
            self::said('cost', $cost),
        );
    }

    /** The plugin that declared it. */
    public function plugin(): APluginId
    {
        return $this->plugin;
    }

    /** Where it goes, as the plugin's manifest named it. */
    public function destination(): string
    {
        return $this->destination;
    }

    /** Why it may be made. */
    public function purpose(): string
    {
        return $this->purpose;
    }

    /** Whether this machine's settings let it go out. */
    public function allowed(): WhetherItIsAllowed
    {
        return $this->allowed;
    }

    /** The setting that refuses it. */
    public function switch(): string
    {
        return $this->switch;
    }

    /** What stops working once it is refused. */
    public function cost(): string
    {
        return $this->cost;
    }

    /** A word, refused where it is blank. */
    private static function said(string $field, string $word): string
    {
        if (trim($word) === '') {
            throw RequestSaysNothing::about($field);
        }

        return $word;
    }
}
