<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Installing a plugin, agreed to against the reading the operator was shown.
 *
 * **Two agreements, never one.** The yes to the install quotes the reading's
 * name; each value a recipe would carry elsewhere is approved as itself, and
 * only an approval the reading listed is carried. One the operator left off is
 * left off: what an install missing an approval comes to is the stack's
 * answer, not this app's.
 */
final readonly class APluginInstallAgreed
{
    private function __construct(private APluginSource $source, private string $agreement, private PluginLines $approved) {}

    /** Agreed against that reading, with these values approved as it spells each; an answer that was not a reading is refused. */
    public static function after(ThePlugins $reading, APluginSource $source, PluginLines $approved): self
    {
        if ($reading->agreement() === '') {
            throw PluginInstallWasNotRehearsed::becauseNothingWasRead();
        }

        return new self($source, $reading->agreement(), $approved->alsoIn($reading->approvals()));
    }

    /** Where the plugin comes from, as the reading was asked about it. */
    public function source(): APluginSource
    {
        return $this->source;
    }

    /** The name of the reading agreed to. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** Every value approved, as the reading spells each. */
    public function approved(): PluginLines
    {
        return $this->approved;
    }
}
