<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** What proving a plugin again would ask, or came to, flattened for a template. */
final readonly class APluginReproofAsShown
{
    /**
     * @param string                             $plugin     the plugin it is about, by its id
     * @param bool                               $isAReading whether the adapters are not yet asked
     * @param bool                               $agreeable  whether the stack named an offer a yes may carry back
     * @param string                             $headline   the catalogue key for what it would ask or came to
     * @param list<AProofAsShown>                $proofs     each proof, what it asks and what it establishes
     * @param list<AnAnswerOutOfContractAsShown> $kept       each answer kept against the plugin, by the capability a pass would clear
     */
    public function __construct(
        public string $plugin,
        public bool $isAReading,
        public bool $agreeable,
        public string $headline,
        public array $proofs,
        public array $kept,
    ) {}
}
