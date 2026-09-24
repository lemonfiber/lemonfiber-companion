<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack how it shares its line produced, flattened for a template.
 *
 * **What the stack may not know is absent, not zero.** `$measured` is null
 * where nothing measured the line and `$cap` is null where no cap was
 * declared; the template draws a sentence for each absence. The two optional
 * sentences are empty where the stack said nothing, and the template branches
 * on them rather than printing a blank.
 */
final readonly class HowTheLineTurnedOutToBe
{
    /**
     * @param string       $standsSaid  the catalogue key for where the line stands, or empty where nothing answered
     * @param string       $means       what that means for the household
     * @param string       $downSays    the download limit, in the stack's sentence
     * @param string       $upSays      the upload limit, in the stack's sentence
     * @param list<string> $cautions    what to know before trusting this
     * @param list<string> $untouched   what is outside every limit
     * @param string       $spentCap    what a spent cap is doing, or empty
     * @param string       $uploadCost  what throttling the upload costs, or empty
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $standsSaid,
        public string $means,
        public string $downSays,
        public string $upSays,
        public array $cautions,
        public array $untouched,
        public ?TheLineAsMeasured $measured,
        public ?TheCapAsShown $cap,
        public string $spentCap,
        public string $uploadCost,
    ) {}
}
