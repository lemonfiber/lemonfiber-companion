<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

use function trim;

/**
 * What the stack says of its verified plugin catalogue: listed, switched off, or not reached.
 *
 * Three answers, and none of them is an empty list standing in for another: a
 * catalogue that was not asked, or could not be read, is never one that
 * offers nothing.
 */
final readonly class ThePluginCatalogue
{
    /**
     * Exactly one of the three is held, so no state carries a word it does not have.
     *
     * @param array{Instant, int, int, list<ACatalogueEntry>}|null $listed  when it was published, its serial, how many could not be listed, and each entry
     * @param array{string, string}|null                           $failure the problem's code and summary
     */
    private function __construct(
        private ?array $listed,
        private ?ASettingThatReaches $setting,
        private ?array $failure,
    ) {}

    /** The verified index: when it was published, its serial, each entry it lists, and how many it holds that could not be listed. */
    public static function listed(Instant $published, int $serial, int $unreadable, ACatalogueEntry ...$entries): self
    {
        return new self([$published, $serial, $unreadable, array_values($entries)], setting: null, failure: null);
    }

    /** Reaching the catalogue is switched off, and this is the setting that turns it on. */
    public static function switchedOff(ASettingThatReaches $setting): self
    {
        return new self(listed: null, setting: $setting, failure: null);
    }

    /** The index could not be fetched, verified or read, with the problem an install by name would be refused with; a blank code or summary is refused. */
    public static function unreached(string $code, string $summary): self
    {
        foreach (['failure.code' => $code, 'failure.summary' => $summary] as $field => $word) {
            if (trim($word) === '') {
                throw ThePluginCatalogueIsUnreadable::blank($field);
            }
        }

        return new self(listed: null, setting: null, failure: [trim($code), trim($summary)]);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TListed of object
     * @template TOff of object
     * @template TFailed of object
     *
     * @param Closure(Instant, int, int, list<ACatalogueEntry>): TListed $listed     when it was published, its serial, how many could not be listed, and each entry
     * @param Closure(ASettingThatReaches): TOff                        $switchedOff
     * @param Closure(string, string): TFailed                          $unreached  the problem's code and summary
     *
     * @return TListed|TOff|TFailed
     */
    public function either(Closure $listed, Closure $switchedOff, Closure $unreached): object
    {
        return match (true) {
            $this->listed !== null => $listed(...$this->listed),
            $this->setting instanceof ASettingThatReaches => $switchedOff($this->setting),
            default => $unreached(...$this->failed()),
        };
    }

    /**
     * The problem's code and summary, which a catalogue neither listed nor switched off always holds.
     *
     * @return array{string, string}
     */
    private function failed(): array
    {
        return $this->failure ?? throw ThePluginCatalogueIsUnreadable::blank('failure');
    }
}
