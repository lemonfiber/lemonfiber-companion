<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What asking a stack about quality produced, flattened for a template.
 */
final readonly class TheQualityTurnedOutToBe
{
    /**
     * @param list<APresetAsShown> $presets     the presets in force, the overall choice first
     * @param string               $becameSaid  the catalogue key for what became of the choice, or empty where nothing came back
     * @param list<string>         $heldBecause what playing each held preset costs, where the choice was held
     * @param bool                 $customised  whether the configuration was edited by hand
     * @param list<string>         $offeredPresets every preset a choice may be made from, in the stack's words
     * @param list<string>         $offeredFormats every format music may take, in the stack's words
     * @param list<string>         $offeredKinds   every kind of media a choice may be set apart for, in the stack's words
     * @param string               $changes        what the choice changes, in the stack's words, or empty where nothing was chosen
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $presets,
        public AFormatAsShown $music,
        public string $becameSaid,
        public array $heldBecause,
        public bool $customised,
        public array $offeredPresets,
        public array $offeredFormats,
        public array $offeredKinds,
        public string $changes,
    ) {}
}
