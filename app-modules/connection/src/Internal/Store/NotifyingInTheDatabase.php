<?php

declare(strict_types=1);

namespace Modules\Connection\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

use function is_int;
use function is_string;

use Modules\Connection\Internal\KeptSettings;
use Modules\Connection\Internal\NotifyingKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use stdClass;

/**
 * {@see NotifyingKept} over the app's own database: one table, `connection_notifying_kept`, one row a stack.
 *
 * A database that refuses is answered as nothing kept and nothing forgotten:
 * a phone that cannot keep the choices tells everything the stack raises,
 * which is what it does until the operator chooses.
 */
final readonly class NotifyingInTheDatabase implements NotifyingKept
{
    private const string TABLE = 'connection_notifying_kept';

    public function __construct(private ConnectionInterface $database) {}

    public function keep(SealedStack $stack, SealedPayload $payload, Shape $shape, Instant $notedAt): Noted
    {
        try {
            $this->database->table(self::TABLE)->upsert(
                [[
                    'stack_hash' => $stack->forTheStore(),
                    'shape' => $shape->value,
                    'noted_at' => $notedAt->epochSeconds(),
                    'payload' => $payload->forTheStore(),
                ]],
                ['stack_hash'],
                ['shape', 'noted_at', 'payload'],
            );
        } catch (QueryException) {
            return Noted::notKept();
        }

        return Noted::downAt($notedAt);
    }

    public function found(SealedStack $stack): KeptSettings
    {
        try {
            return $this->asKept($this->database->table(self::TABLE)->where('stack_hash', $stack->forTheStore())->first(['shape', 'payload']));
        } catch (QueryException) {
            return KeptSettings::none();
        }
    }

    public function forget(SealedStack $stack): Forgotten
    {
        try {
            return Forgotten::rows($this->database->table(self::TABLE)->where('stack_hash', $stack->forTheStore())->delete());
        } catch (QueryException) {
            return Forgotten::nothing();
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
            : KeptSettings::thatThisBuildCannotRead();
    }
}
