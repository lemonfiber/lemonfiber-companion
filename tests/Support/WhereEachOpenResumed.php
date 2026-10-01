<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_map;
use function array_values;
use function is_string;

use Lemonfiber\Sdk\Contract\Api;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Response;

/**
 * Which event each stream a mock answered was opened after, in the order they were opened.
 *
 * Read off the requests the mock was sent, because the header that asks to
 * resume is the whole of what the stack is told: an adapter that kept where a
 * stream left off and never sent it would resume nothing.
 */
final readonly class WhereEachOpenResumed
{
    /** @return list<?string> the event each open resumed after, or none where it asked from the start */
    public static function in(MockClient $mock): array
    {
        return array_values(array_map(
            static function (Response $answered): ?string {
                $after = $answered->getPendingRequest()->headers()->get(Api::RESUME_HEADER);

                return is_string($after) ? $after : null;
            },
            $mock->getRecordedResponses(),
        ));
    }
}
