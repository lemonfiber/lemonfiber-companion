<?php

declare(strict_types=1);

namespace Tests\Support;

use function fclose;
use function fwrite;
use function implode;
use function is_resource;

use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Saloon\Http\Faking\MockResponse;

use const STREAM_IPPROTO_IP;
use const STREAM_PF_UNIX;

use function stream_set_blocking;

use const STREAM_SOCK_STREAM;

use function stream_socket_pair;

/**
 * A stack's event stream that stays open between takes, as a held stream does on a phone.
 *
 * A `MockResponse` body is read to its end in one take, so every stream it
 * stands in for has ended by the time anything looks at it. This answers with
 * one end of a socket pair instead: a read finds what the stack has said so
 * far and then nothing, without the stream ending, and the stack can say more
 * between two takes or end the stream when a case wants it to.
 */
final class AStreamThatStaysOpen extends MockResponse
{
    /** @var resource */
    private $stack;

    /** @var resource */
    private $phone;

    private function __construct()
    {
        parent::__construct();

        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($pair === false) {
            throw new RuntimeException('No socket pair could be opened to stand in for a stream.');
        }

        [$this->stack, $this->phone] = $pair;
        stream_set_blocking($this->stack, enable: false);
    }

    public function __destruct()
    {
        $this->ends();
    }

    /** A stream that has said these events so far, and stays open. */
    public static function saying(string ...$events): self
    {
        $stream = new self();
        $stream->says(...$events);

        return $stream;
    }

    /** The stack says more on the stream that is already open; a held stream reads one chunk ahead, so it reaches the take after next. */
    public function says(string ...$events): void
    {
        fwrite($this->stack, implode('', $events));
    }

    /** The stack ends the stream. */
    public function ends(): void
    {
        if (is_resource($this->stack)) {
            fclose($this->stack);
        }
    }

    #[Override]
    public function createPsrResponse(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory): ResponseInterface
    {
        return $responseFactory->createResponse($this->status())->withBody($streamFactory->createStreamFromResource($this->phone));
    }
}
