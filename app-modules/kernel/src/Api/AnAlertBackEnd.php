<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One back-end that delivers this machine's alerts: what it speaks, where it
 * delivers with no credential in it, who chose it, how far it is proven, and
 * how its deliveries went.
 */
final readonly class AnAlertBackEnd
{
    private function __construct(
        private AlertBackEndId $id,
        private KindOfAlertBackEnd $kind,
        private string $where,
        private WhoChoseIt $chosenBy,
        private WhereItIsHosted $hosted,
        private HowFarItIsProven $proof,
        private HowItsDeliveriesWent $deliveries,
    ) {}

    public static function configured(
        AlertBackEndId $id,
        KindOfAlertBackEnd $kind,
        string $where,
        WhoChoseIt $chosenBy,
        WhereItIsHosted $hosted,
        HowFarItIsProven $proof,
        HowItsDeliveriesWent $deliveries,
    ): self {
        if (trim($where) === '') {
            throw WhereAlertsGoSaysNothing::about('where a back-end delivers');
        }

        return new self($id, $kind, $where, $chosenBy, $hosted, $proof, $deliveries);
    }

    public function id(): AlertBackEndId
    {
        return $this->id;
    }

    public function kind(): KindOfAlertBackEnd
    {
        return $this->kind;
    }

    /** The host and topic or path it reaches, with no credential in it. */
    public function where(): string
    {
        return $this->where;
    }

    public function chosenBy(): WhoChoseIt
    {
        return $this->chosenBy;
    }

    public function hosted(): WhereItIsHosted
    {
        return $this->hosted;
    }

    public function proof(): HowFarItIsProven
    {
        return $this->proof;
    }

    public function deliveries(): HowItsDeliveriesWent
    {
        return $this->deliveries;
    }
}
