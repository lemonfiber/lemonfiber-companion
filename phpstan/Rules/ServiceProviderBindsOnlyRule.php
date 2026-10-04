<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_key_first;

use Illuminate\Support\ServiceProvider;

use function in_array;
use function is_array;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;
use function str_starts_with;

/**
 * A9 — a service provider binds, and does not work.
 *
 * Twelve module providers run before the first frame is drawn. Anything one of
 * them *does* — a read, a request, a resolved object graph — happens while the
 * operator is looking at a splash screen, on a phone, possibly on a network
 * that is not there. A usable frame must arrive without waiting on a read,
 * and a provider is the easiest place in Laravel to break that without anyone
 * noticing: the code looks like configuration.
 *
 * The distinction the rule draws is between doing a thing and describing how it
 * will be done. `$this->app->bind(Stack::class, fn ($app) => new SdkStack(
 * $app->make(Client::class)))` is fine — the `make` inside the closure runs when
 * something first asks for a Stack, which is usually never during boot. The same
 * `make` outside the closure builds the graph immediately. So the walk stops at
 * every closure boundary, and what it reports is only what runs during boot.
 *
 * **What runs during boot is followed into the provider's own methods.** A
 * `register()` that hands its bindings to `$this->bindTheStores()` runs that
 * method at boot as surely as its own lines, so a method called on `$this`
 * from `register()` or `boot()`, and every one that calls in turn, is read
 * too. A method handed over as `$this->theScanner(...)` is a binding's
 * factory, called when something asks, and is not followed.
 *
 * @implements Rule<InClassNode>
 */
final class ServiceProviderBindsOnlyRule implements Rule
{
    /** Reading, waiting, or reaching outside — none of it belongs before a frame. */
    private const array FORBIDDEN_FUNCTIONS = [
        'file_get_contents', 'file_put_contents', 'fopen', 'fread', 'fwrite',
        'glob', 'scandir', 'file_exists', 'is_file', 'is_dir', 'unlink', 'mkdir',
        'curl_init', 'curl_exec', 'fsockopen', 'stream_socket_client',
        'sleep', 'usleep', 'env', 'app', 'resolve',
    ];

    /** Facades whose first call opens a connection, a file or a socket. */
    private const array FORBIDDEN_FACADES = [
        'Illuminate\Support\Facades\Http',
        'Illuminate\Support\Facades\DB',
        'Illuminate\Support\Facades\Storage',
        'Illuminate\Support\Facades\Cache',
        'Illuminate\Support\Facades\Schema',
        'Illuminate\Support\Facades\Artisan',
    ];

    /** Container methods that build the graph now rather than describing it. */
    private const array EAGER_CONTAINER_METHODS = ['make', 'makewith', 'get', 'build', 'resolve'];

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->getClassReflection()->isSubclassOf(ServiceProvider::class)) {
            return [];
        }

        $found = [];

        foreach ($this->methodsRunningAtBoot($node->getOriginalNode()->getMethods()) as $method) {
            foreach ($this->callsRunningAtBoot($method->stmts ?? []) as $call) {
                $offence = $this->offenceIn($call);

                if ($offence === null) {
                    continue;
                }

                $found[] = RuleErrorBuilder::message(sprintf(
                    'A9 — a service provider binds and does not work. %s runs before the '
                    . 'first frame is drawn, on a phone, possibly with no network. Describe '
                    . 'how the thing will be built and let the first caller pay for it: put '
                    . 'it in a binding closure, which this rule deliberately does not look '
                    . 'inside. A frame the operator can use must not wait on a read (A9, '
                    . 'N1-R36).',
                    $offence,
                ))
                    ->identifier('lemonfiber.serviceProviderDoesWork')
                    ->line($call->getStartLine())
                    ->build();
            }
        }

        return $found;
    }

    /**
     * `register()`, `boot()`, and every method of the provider they call on `$this` as they run, followed to the end.
     *
     * @param array<int, ClassMethod> $methods
     *
     * @return array<string, ClassMethod>
     */
    private function methodsRunningAtBoot(array $methods): array
    {
        $byName = [];

        foreach ($methods as $method) {
            $byName[$method->name->toLowerString()] = $method;
        }

        $waiting = array_intersect_key($byName, array_flip(['register', 'boot']));
        $read = [];

        while ($waiting !== []) {
            $name = (string) array_key_first($waiting);
            $read[$name] = $waiting[$name];
            unset($waiting[$name]);

            foreach ($this->methodsCalledOnItself($read[$name]) as $called) {
                if (array_key_exists($called, $byName) && ! array_key_exists($called, $read)) {
                    $waiting[$called] = $byName[$called];
                }
            }
        }

        return $read;
    }

    /**
     * The methods this one calls on `$this` as it runs, by name; one handed over as a callable is not called.
     *
     * @return list<string>
     */
    private function methodsCalledOnItself(ClassMethod $method): array
    {
        $called = [];

        foreach ($this->callsRunningAtBoot($method->stmts ?? []) as $call) {
            if ($call instanceof MethodCall
                && ! $call->isFirstClassCallable()
                && $call->var instanceof Variable
                && $call->var->name === 'this'
                && $call->name instanceof Node\Identifier) {
                $called[] = $call->name->toLowerString();
            }
        }

        return $called;
    }

    /**
     * Every call in the method body that actually runs during boot.
     *
     * A closure passed to `bind()` is a description of future work, so the walk
     * stops at one rather than reporting the `make` that will run later, when
     * something asks.
     *
     * @param array<int, Node> $nodes
     *
     * @return list<Node>
     */
    private function callsRunningAtBoot(array $nodes): array
    {
        $found = [];

        foreach ($nodes as $node) {
            if ($node instanceof Closure || $node instanceof ArrowFunction) {
                continue;
            }

            if ($node instanceof FuncCall || $node instanceof StaticCall || $node instanceof MethodCall) {
                $found[] = $node;
            }

            foreach ($node->getSubNodeNames() as $name) {
                /** @var mixed $child */
                $child = $node->{$name};

                $found = [...$found, ...$this->callsRunningAtBoot($this->asNodes($child))];
            }
        }

        return $found;
    }

    /**
     * @return array<int, Node>
     */
    private function asNodes(mixed $child): array
    {
        if ($child instanceof Node) {
            return [$child];
        }

        if (! is_array($child)) {
            return [];
        }

        $found = [];

        foreach ($child as $item) {
            if ($item instanceof Node) {
                $found[] = $item;
            }
        }

        return $found;
    }

    /** What this call does that a provider may not, or null if it is fine. */
    private function offenceIn(Node $call): ?string
    {
        if ($call instanceof FuncCall && $call->name instanceof Node\Name) {
            $name = $call->name->toLowerString();

            return in_array($name, self::FORBIDDEN_FUNCTIONS, strict: true)
                ? sprintf('Calling %s() here', $name)
                : null;
        }

        if ($call instanceof StaticCall && $call->class instanceof Node\Name) {
            $name = $call->class->toString();

            if (in_array($name, self::FORBIDDEN_FACADES, strict: true)) {
                return sprintf('Calling %s here', $name);
            }

            return str_starts_with($name, 'Native\Mobile\Facades\\')
                ? sprintf('Calling %s here', $name)
                : null;
        }

        return $call instanceof MethodCall ? $this->eagerContainerCall($call) : null;
    }

    /** `$this->app->make(...)` outside a closure, which builds the graph now. */
    private function eagerContainerCall(MethodCall $call): ?string
    {
        if (! $call->name instanceof Node\Identifier) {
            return null;
        }

        if (! in_array($call->name->toLowerString(), self::EAGER_CONTAINER_METHODS, strict: true)) {
            return null;
        }

        if (! $call->var instanceof PropertyFetch) {
            return null;
        }

        if (! $call->var->var instanceof Variable || $call->var->var->name !== 'this') {
            return null;
        }

        return $call->var->name instanceof Node\Identifier && $call->var->name->toString() === 'app'
            ? sprintf('Resolving $this->app->%s() here', $call->name->toString())
            : null;
    }
}
