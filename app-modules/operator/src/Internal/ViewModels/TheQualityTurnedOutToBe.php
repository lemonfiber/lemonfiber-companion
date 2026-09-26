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
     */
    public function __construct(
        public HowTheReadingWent $went,
        public array $presets,
        public AFormatAsShown $music,
        public string $becameSaid,
        public array $heldBecause,
        public bool $customised,
    ) {}
}
