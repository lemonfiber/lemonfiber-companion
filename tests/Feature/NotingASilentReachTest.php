<?php

declare(strict_types=1);

use Modules\Sdk\Api\ClientsThatAskTheDevice;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

// A silent reach is noted for a developer in a debug build's log, and in no
// other build. Asked of the container, which is where the build's kind meets
// the log: a release must be handed a log that keeps nothing.

/** The log the container hands the clients that ask the device, in a build of this kind. */
function theLogNotesGoToWhereDebugIs(bool $debug): object
{
    config()->set('app.debug', $debug);
    app()->forgetInstance(ClientsThatAskTheDevice::class);

    $notes = new ReflectionProperty(ClientsThatAskTheDevice::class, 'notes')->getValue(app(ClientsThatAskTheDevice::class));

    return is_object($notes) ? $notes : throw new RuntimeException('The clients hold no log.');
}

it('hands a release a log that keeps nothing', function (): void {
    expect(theLogNotesGoToWhereDebugIs(debug: false))->toBeInstanceOf(NullLogger::class);
});

it('hands a debug build the application\'s own log', function (): void {
    $notes = theLogNotesGoToWhereDebugIs(debug: true);

    expect($notes)->toBeInstanceOf(LoggerInterface::class);
    expect($notes)->not->toBeInstanceOf(NullLogger::class);
});
