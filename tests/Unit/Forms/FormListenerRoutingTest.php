<?php

/**
 * `Form::listeners()` is per-form, not global.
 *
 * FormsServiceProvider used to walk every registered form at boot and register each distinct listener id
 * once on the shared FormSubmittedEvent, deduplicated across ALL forms. That made the list global: one
 * form declaring a listener meant every form ran it, so overriding `listeners()` could not remove
 * anything, and a site that replaced the notification with its own branded one sent two emails per
 * submission.
 *
 * @package Corex\Tests\Unit\Forms
 */

declare(strict_types=1);

use Corex\Container\ContainerInterface;
use Corex\Events\EventDispatcher;
use Corex\Events\ListenerProvider;
use Corex\Forms\Form;
use Corex\Forms\FormRegistry;
use Corex\Forms\FormsServiceProvider;
use Corex\Forms\Submission\FormSubmittedEvent;
use Corex\Support\BootLogger;

/** Records which listeners ran, in order. */
final class ListenerSpy
{
    /** @var list<string> */
    public static array $ran = [];
}

final class StoreSpy
{
    public function __invoke(object $event): void
    {
        ListenerSpy::$ran[] = 'store';
    }
}

final class EmailSpy
{
    public function __invoke(object $event): void
    {
        ListenerSpy::$ran[] = 'email';
    }
}

/** A form whose listener list the test controls. */
final class SpyForm extends Form
{
    /** @param list<class-string> $listenerIds */
    public function __construct(string $slug, private readonly array $listenerIds)
    {
        $this->slug = $slug;
    }

    public function listeners(): array
    {
        return $this->listenerIds;
    }
}

/** Resolves only what registerListeners() asks for; everything else is built by class name. */
final class RoutingContainerDouble implements ContainerInterface
{
    public function __construct(
        private readonly FormRegistry $registry,
        private readonly ListenerProvider $provider,
    ) {
    }

    public function make(string $id, array $parameters = []): mixed
    {
        return match ($id) {
            FormRegistry::class => $this->registry,
            ListenerProvider::class => $this->provider,
            default => new $id(),
        };
    }

    public function get(string $id): mixed
    {
        return $this->make($id);
    }

    public function has(string $id): bool
    {
        return true;
    }

    public function bind(string $id, Closure|string|null $concrete = null): void
    {
    }

    public function singleton(string $id, Closure|string|null $concrete = null): void
    {
    }

    public function instance(string $id, object $instance): object
    {
        return $instance;
    }
}

/** Wire the provider's real registerListeners() against a controlled registry. */
function listenerRoutingDispatcher(FormRegistry $registry): EventDispatcher
{
    $provider = new ListenerProvider();
    $serviceProvider = new FormsServiceProvider(new RoutingContainerDouble($registry, $provider));

    // registerListeners() is private; reaching it directly keeps this a unit test of the routing rule
    // rather than a full provider boot, which would pull in blocks, REST and the whole mail graph.
    $register = new ReflectionMethod(FormsServiceProvider::class, 'registerListeners');
    $register->setAccessible(true);
    $register->invoke($serviceProvider);

    return new EventDispatcher($provider, new BootLogger(false));
}

function twoFormRegistry(): FormRegistry
{
    $registry = new FormRegistry();
    $registry->register(new SpyForm('with-email', [StoreSpy::class, EmailSpy::class]));
    $registry->register(new SpyForm('store-only', [StoreSpy::class]));

    return $registry;
}

beforeEach(function () {
    ListenerSpy::$ran = [];
});

it('runs only the submitted form\'s own listeners', function () {
    $dispatcher = listenerRoutingDispatcher(twoFormRegistry());

    $dispatcher->dispatch(new FormSubmittedEvent('store-only', ['email' => 'a@b.com']));

    // The sibling form declares EmailSpy; that must not leak onto this submission.
    expect(ListenerSpy::$ran)->toBe(['store']);
});

it('still runs every listener a form does declare, in order', function () {
    $dispatcher = listenerRoutingDispatcher(twoFormRegistry());

    $dispatcher->dispatch(new FormSubmittedEvent('with-email', ['email' => 'a@b.com']));

    expect(ListenerSpy::$ran)->toBe(['store', 'email']);
});

it('ignores a slug no registered form owns, such as a database flow', function () {
    $dispatcher = listenerRoutingDispatcher(twoFormRegistry());

    $dispatcher->dispatch(new FormSubmittedEvent('some-db-flow', ['email' => 'a@b.com']));

    expect(ListenerSpy::$ran)->toBe([]);
});

/*
 * ⚠ UPSTREAM BEHAVIOUR, not ours. Reported.
 *
 * Our fork deduplicated listener ids before dispatch. Upstream v0.40.0's `registerListeners()` is a bare
 * `foreach ($form->listeners() as $listenerId)`, so a form that names the same listener twice runs it
 * twice — which for Perego's `StoreSubmissionListener` would mean two stored rows and two notification
 * emails for one submission.
 *
 * Latent, not live: both Perego forms declare exactly one listener, and
 * `tests/Unit/Forms/PeregoFormListenersTest` (Perego side) now asserts that stays true. We take
 * upstream's version rather than re-forking `FormsServiceProvider`, and pin the behaviour here so a
 * future upstream dedupe turns this red on purpose.
 */
it('runs a duplicated listener id twice — upstream behaviour, reported', function () {
    $registry = new FormRegistry();
    $registry->register(new SpyForm('dupe', [StoreSpy::class, StoreSpy::class]));

    listenerRoutingDispatcher($registry)->dispatch(new FormSubmittedEvent('dupe', ['email' => 'a@b.com']));

    expect(ListenerSpy::$ran)->toBe(['store', 'store']);
});
