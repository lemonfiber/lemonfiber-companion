<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One value a recipe could carry to one destination, with its own switch where it asks for an approval.
 *
 * The switch is named by its place among the install's approvals, never by
 * the value: what is typed into a call is never the value itself.
 *
 * **Whether it asks for an approval is the stack's answer**, carried on its
 * own: a pair the stack gave no approval to stays on this machine, and only
 * that pair is drawn as staying. A switch is something this account offers,
 * and a pair can ask for an approval no switch here gives.
 */
final readonly class AValueCarriedAsShown
{
    /**
     * @param string   $value    what the value is called within the recipe
     * @param string   $origin   whose value it is, or empty
     * @param string   $to       where it would go, by the manifest's name
     * @param string   $release  why it is released away from the service it was read from, or empty
     * @param string   $from     the service it is read from, where it is released, or empty
     * @param bool     $asksForApproval whether the stack says it leaves the machine or releases a value, and so asks for an approval
     * @param int|null $approval its switch's place among the approvals this account offers, or null where it offers none for it
     * @param bool     $approved whether the operator has approved it
     */
    public function __construct(
        public string $value,
        public string $origin,
        public string $to,
        public string $release,
        public string $from,
        public bool $asksForApproval,
        public ?int $approval,
        public bool $approved,
    ) {}
}
