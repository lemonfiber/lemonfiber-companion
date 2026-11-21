<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

use function trim;

/**
 * What the stack says of its verified plugin catalogue: listed, switched off, or not reached (ARCH-R450).
 *
 * Three answers, and none of them is an empty list standing in for another: a
 * catalogue that was not asked, or could not be read, is never one that
 * offers nothing.
 */
final readonly class ThePluginCatalogue
{
    /**
     * @param list<ACatalogueEntry> $entries
     */
    private function __construct(
        private ?Instant $published,
        private int $serial,
        private int $unreadable,
        private array $entries,
        private ?ASettingThatReaches $setting,
        private string $code,
        private string $summary,
    ) {}

    /** The verified index: when it was published, its serial, each entry it lists, and how many it holds that could not be listed. */
    public static function listed(Instant $published, int $serial, int $unreadable, ACatalogueEntry ...$entries): self
    {
        return new self($published, $serial, $unreadable, array_values($entries), setting: null, code: '', summary: '');
    }

    /** Reaching the catalogue is switched off, and this is the setting that turns it on. */
    public static function switchedOff(ASettingThatReaches $setting): self
    {
        return new self(published: null, serial: 0, unreadable: 0, entries: [], setting: $setting, code: '', summary: '');
    }

    /** The index could not be fetched, verified or read, with the problem an install by name would be refused with; a blank code or summary is refused. */
    public static function unreached(string $code, string $summary): self
    {
        foreach (['failure.code' => $code, 'failure.summary' => $summary] as $field => $word) {
            if (trim($word) === '') {
                throw ThePluginCatalogueIsUnreadable::blank($field);
            }
        }

        return new self(published: null, serial: 0, unreadable: 0, entries: [], setting: null, code: trim($code), summary: trim($summary));
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
            $this->published instanceof Instant => $listed($this->published, $this->serial, $this->unreadable, $this->entries),
            $this->setting instanceof ASettingThatReaches => $switchedOff($this->setting),
            default => $unreached($this->code, $this->summary),
        };
    }
}
