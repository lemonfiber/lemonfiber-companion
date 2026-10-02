<?php

declare(strict_types=1);

namespace Modules\News\Internal\Store;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;

use function is_int;
use function is_string;

use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\News\Internal\KeptNews;
use Modules\News\Internal\NewsKept;
use stdClass;

/**
 * {@see NewsKept} over the app's own database: one table, `news_kept`, one row a stack.
 *
 * A database that refuses is answered as nothing kept and nothing forgotten,
 * for the reason every store here answers so: what is new is a convenience,
 * and a phone that cannot keep it still shows every stack.
 */
final readonly class NewsInTheDatabase implements NewsKept
{
    private const string TABLE = 'news_kept';

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

    public function found(SealedStack $stack): KeptNews
    {
        try {
            return $this->asKept($this->database->table(self::TABLE)->where('stack_hash', $stack->forTheStore())->first(['shape', 'payload']));
        } catch (QueryException) {
            return KeptNews::none();
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

    private function asKept(?stdClass $row): KeptNews
    {
        if (! $row instanceof stdClass) {
            return KeptNews::none();
        }

        $shape = is_int($row->shape) ? Shape::tryFrom($row->shape) : null;

        if (! $shape instanceof Shape || ! is_string($row->payload)) {
            return KeptNews::thatThisBuildCannotRead();
        }

        return KeptNews::found(SealedPayload::of($row->payload), $shape);
    }
}
