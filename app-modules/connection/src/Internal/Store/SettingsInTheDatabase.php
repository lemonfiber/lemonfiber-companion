<?php

declare(strict_types=1);

namespace Modules\Connection\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

use function is_int;
use function is_string;

use Modules\Connection\Internal\KeptSettings;
use Modules\Connection\Internal\SettingsKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\Shape;
use stdClass;

/**
 * This module's settings, in its own table, as one sealed row.
 *
 * A store that cannot be written or read answers as one that kept nothing:
 * a setting that did not stick is the setting's default, never an error.
 */
final readonly class SettingsInTheDatabase implements SettingsKept
{
    private const string TABLE = 'connection_settings';

    /** The one row the settings are kept in. */
    private const int THE_ROW = 1;

    public function __construct(private ConnectionInterface $database) {}

    public function keep(SealedPayload $payload, Shape $shape, Instant $setAt): Noted
    {
        try {
            $this->database->table(self::TABLE)->upsert(
                [[
                    'row' => self::THE_ROW,
                    'shape' => $shape->value,
                    'set_at' => $setAt->epochSeconds(),
                    'payload' => $payload->forTheStore(),
                ]],
                ['row'],
                ['shape', 'set_at', 'payload'],
            );
        } catch (QueryException) {
            return Noted::notKept();
        }

        return Noted::downAt($setAt);
    }

    public function kept(): KeptSettings
    {
        try {
            return $this->asKept($this->database->table(self::TABLE)->where('row', self::THE_ROW)->first(['shape', 'payload']));
        } catch (QueryException) {
            return KeptSettings::none();
        }
    }

    public function forgetEverything(): Forgotten
    {
        try {
            return Forgotten::rows($this->database->table(self::TABLE)->delete());
        } catch (QueryException) {
            return Forgotten::nothing();
        }
    }

    private function asKept(?stdClass $row): KeptSettings
    {
        if (! $row instanceof stdClass) {
            return KeptSettings::none();
        }

        $shape = is_int($row->shape) ? Shape::tryFrom($row->shape) : null;

        return $shape instanceof Shape && is_string($row->payload)
            ? KeptSettings::found(SealedPayload::of($row->payload), $shape)
            : KeptSettings::none();
    }
}
