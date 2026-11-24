<?php

declare(strict_types=1);

namespace Modules\Health\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

use function is_int;

use Modules\Health\Internal\MissingAlertsKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedStack;

/** Which stacks may be missing alerts, one row a stack in the phone's own database. */
final readonly class MissingAlertsInTheDatabase implements MissingAlertsKept
{
    private const string TABLE = 'health_alerts_may_be_missing';

    public function __construct(private ConnectionInterface $database) {}

    public function mark(SealedStack $stack, Instant $notedAt): Noted
    {
        try {
            $this->database->table(self::TABLE)->upsert(
                [['stack_hash' => $stack->forTheStore(), 'noted_at' => $notedAt->epochSeconds()]],
                ['stack_hash'],
                ['noted_at'],
            );
        } catch (QueryException) {
            return Noted::notKept();
        }

        return Noted::downAt($notedAt);
    }

    public function whenMarked(SealedStack $stack): Noted
    {
        try {
            $at = $this->database->table(self::TABLE)->where('stack_hash', $stack->forTheStore())->value('noted_at');
        } catch (QueryException) {
            return Noted::notKept();
        }

        return is_int($at) ? Noted::downAt(Instant::atEpochSeconds($at)) : Noted::notKept();
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
}
