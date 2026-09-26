<?php

declare(strict_types=1);

use Modules\Sdk\Api\Fields\AlertsField;
use Modules\Sdk\Api\Fields\ArchivesField;
use Modules\Sdk\Api\Fields\BandwidthField;
use Modules\Sdk\Api\Fields\ClientsField;
use Modules\Sdk\Api\Fields\ConfigField;
use Modules\Sdk\Api\Fields\CredentialsField;
use Modules\Sdk\Api\Fields\DoctorField;
use Modules\Sdk\Api\Fields\FrontDoorField;
use Modules\Sdk\Api\Fields\GlossaryField;
use Modules\Sdk\Api\Fields\HeldField;
use Modules\Sdk\Api\Fields\HistoryField;
use Modules\Sdk\Api\Fields\HostingField;
use Modules\Sdk\Api\Fields\HouseholdField;
use Modules\Sdk\Api\Fields\InvitationField;
use Modules\Sdk\Api\Fields\JobField;
use Modules\Sdk\Api\Fields\LogField;
use Modules\Sdk\Api\Fields\MigrationField;
use Modules\Sdk\Api\Fields\OutboundField;
use Modules\Sdk\Api\Fields\PreviewField;
use Modules\Sdk\Api\Fields\ProvenanceField;
use Modules\Sdk\Api\Fields\RepairField;
use Modules\Sdk\Api\Fields\SelfUpdateField;
use Modules\Sdk\Api\Fields\SpaceField;
use Modules\Sdk\Api\Fields\StatusField;
use Modules\Sdk\Api\Fields\StoredField;
use Modules\Sdk\Api\Fields\StuckField;
use Modules\Sdk\Api\Fields\TraceField;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\WireField;

// The NamesAWireField contract, run against every enum that names wire fields.
//
// `G2`'s shape with more than two implementations: each enum is one, and a
// reader takes any of them where it takes a field. What every one of them
// promises is that a case's value is the word on the wire, and that a path
// through two of them is written the same way whichever enums they are.

/**
 * Every field every implementation names, the shared words first.
 *
 * @return list<NamesAWireField>
 */
function everyFieldNamedOnTheWire(): array
{
    return [
        ...WireField::cases(),
        ...AlertsField::cases(),
        ...ArchivesField::cases(),
        ...BandwidthField::cases(),
        ...ClientsField::cases(),
        ...ConfigField::cases(),
        ...CredentialsField::cases(),
        ...DoctorField::cases(),
        ...GlossaryField::cases(),
        ...HeldField::cases(),
        ...FrontDoorField::cases(),
        ...InvitationField::cases(),
        ...HistoryField::cases(),
        ...HostingField::cases(),
        ...HouseholdField::cases(),
        ...JobField::cases(),
        ...LogField::cases(),
        ...MigrationField::cases(),
        ...OutboundField::cases(),
        ...PreviewField::cases(),
        ...ProvenanceField::cases(),
        ...RepairField::cases(),
        ...SelfUpdateField::cases(),
        ...SpaceField::cases(),
        ...StatusField::cases(),
        ...StoredField::cases(),
        ...StuckField::cases(),
        ...TraceField::cases(),
        ...UpdateField::cases(),
    ];
}

it('names each field by a word the wire could carry', function (): void {
    foreach (everyFieldNamedOnTheWire() as $field) {
        expect(preg_match('/^[a-z][a-z_]*$/', $field->value))->toBe(1, sprintf('%s::%s is `%s`', $field::class, $field->name, $field->value));
    }
});

it('writes a path through two fields the same way, whichever enums they are in', function (): void {
    foreach (everyFieldNamedOnTheWire() as $field) {
        expect($field->under(WireField::Data))->toBe(sprintf('data.%s', $field->value))
            ->and(WireField::Data->under($field))->toBe(sprintf('%s.data', $field->value))
            ->and($field->under($field))->toBe(sprintf('%s.%s', $field->value, $field->value));
    }
});
