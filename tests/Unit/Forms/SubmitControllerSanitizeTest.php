<?php

/**
 * The REST boundary's form-shaped sanitizer (spec FR-010).
 *
 * The shape is derived from the form's own field types, so a field type the shape does not know
 * about is silently mis-sanitized rather than rejected — which is exactly how multi-value fields
 * were lost: `sanitize_text_field()` returns '' for an array, so a multi-select submitted through
 * this controller reached the handler blank while the flow controller (which does know the type)
 * handled it correctly. These tests pin both halves: lists survive as lists, scalars still get the
 * scalar sanitizer, and undeclared keys still never reach the handler.
 *
 * @package Corex\Tests\Unit\Forms
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use Corex\Container\Container;
use Corex\Events\EventDispatcher;
use Corex\Events\ListenerProvider;
use Corex\Forms\Form;
use Corex\Forms\FormRegistry;
use Corex\Forms\Schema\SchemaResolver;
use Corex\Forms\Submission\FormSubmissionService;
use Corex\Forms\Submission\FormSubmittedEvent;
use Corex\Forms\Submission\SubmitController;
use Corex\Forms\Validation\RuleRegistry;
use Corex\Forms\Validation\Validator;
use Corex\Http\Middleware\Middleware;
use Corex\Http\Middleware\MiddlewareResolver;
use Corex\Http\Middleware\Pipeline;
use Corex\Http\Middleware\Request;
use Corex\Http\Middleware\Response;
use Corex\Support\BootLogger;

/**
 * A headless `WP_REST_Request`: the controller only ever reads a url param, the JSON body and a
 * header off it.
 *
 * Defined here rather than in `tests/bootstrap.php` because this is a fork-only contract test and the
 * bootstrap belongs to upstream — v0.40.0 rebuilt it and doubles only `WP_Post`. A fork test that
 * needs a double upstream does not provide should carry it, or the next framework update breaks the
 * test for a reason that has nothing to do with the behaviour it guards.
 */
if (! class_exists('WP_REST_Request')) {
    /** ArrayAccess because the controller reads the route slug as `$request['slug']`, as WP's own does. */
    class WP_REST_Request implements ArrayAccess
    {
        /** @var array<string,mixed> */
        private array $urlParams = [];

        /** @var array<string,mixed> */
        private array $jsonParams = [];

        /** @var array<string,string> */
        private array $headers = [];

        public function __construct(private string $method = 'GET', private string $route = '')
        {
        }

        /** @param array<string,mixed> $params */
        public function set_url_params(array $params): void
        {
            $this->urlParams = $params;
        }

        /** @param array<string,mixed> $params */
        public function set_json_params(array $params): void
        {
            $this->jsonParams = $params;
        }

        public function set_header(string $name, string $value): void
        {
            $this->headers[strtolower($name)] = $value;
        }

        public function get_header(string $name): ?string
        {
            return $this->headers[strtolower($name)] ?? null;
        }

        /** @return mixed */
        public function get_param(string $name)
        {
            return $this->urlParams[$name] ?? $this->jsonParams[$name] ?? null;
        }

        /** @return array<string,mixed> */
        public function get_json_params(): array
        {
            return $this->jsonParams;
        }

        /** @return array<string,mixed> */
        public function get_params(): array
        {
            return $this->jsonParams + $this->urlParams;
        }

        public function get_method(): string
        {
            return $this->method;
        }

        /**
         * Always empty: these tests submit JSON, and v0.40.0's spec-081 upload pass reads this on every
         * request. An empty set is the honest answer for a body with no files.
         *
         * @return array<string,mixed>
         */
        public function get_file_params(): array
        {
            return [];
        }

        public function get_route(): string
        {
            return $this->route;
        }

        public function offsetExists(mixed $offset): bool
        {
            return $this->get_param((string) $offset) !== null;
        }

        public function offsetGet(mixed $offset): mixed
        {
            return $this->get_param((string) $offset);
        }

        public function offsetSet(mixed $offset, mixed $value): void
        {
            $this->jsonParams[(string) $offset] = $value;
        }

        public function offsetUnset(mixed $offset): void
        {
            unset($this->jsonParams[(string) $offset], $this->urlParams[(string) $offset]);
        }
    }
}

if (! class_exists('WP_REST_Response')) {
    /** The controller only ever constructs one and reads nothing back; these assertions read the event. */
    class WP_REST_Response
    {
        /** @param mixed $data */
        public function __construct(public mixed $data = null, public int $status = 200)
        {
        }

        /** @return mixed */
        public function get_data()
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }
    }
}

final class BriefTestForm extends Form
{
    public string $slug = 'brief';

    /**
     * @var array<string,array{type?:string,rules?:list<string>,label?:string,options?:array<string,string>}>
     */
    protected array $fields = [
        'email' => ['type' => 'email', 'rules' => ['required', 'email']],
        'message' => ['type' => 'textarea', 'rules' => ['required']],
        'services' => [
            'type' => 'multi-select',
            'rules' => ['required'],
            'options' => ['video-editing' => 'Video', 'graphic-design' => 'Design'],
        ],
    ];
}

/**
 * A submission as it arrives at the REST boundary: JSON body, slug in the route, nonce header.
 *
 * @param array<string,mixed> $body
 */
function briefRequest(array $body): WP_REST_Request
{
    $request = new WP_REST_Request('POST', '/corex/v1/forms/brief');
    $request->set_url_params(['slug' => 'brief']);
    $request->set_json_params($body);
    $request->set_header('X-WP-Nonce', 'nonce');

    return $request;
}

/**
 * A real controller over the real submission service and real middleware.
 *
 * Nothing is doubled except the two security aliases, which resolve to pass-throughs: a rejecting
 * alias would short-circuit before the sanitize stage could be observed. Assertions read the
 * dispatched event, so they cover the whole boundary — sanitize, validate, dispatch — rather than
 * one private mapping, which is what actually has to hold for a submission to be stored and emailed.
 *
 * @param list<FormSubmittedEvent> $dispatched captured events, by reference
 */
function sanitizingController(array &$dispatched): SubmitController
{
    $registry = new FormRegistry();
    $registry->register(new BriefTestForm());
    $rules = new RuleRegistry();

    $provider = new ListenerProvider();
    $provider->listen(FormSubmittedEvent::class, function (FormSubmittedEvent $event) use (&$dispatched): void {
        $dispatched[] = $event;
    });

    $service = new FormSubmissionService(
        $registry,
        new SchemaResolver($rules),
        new Validator($rules),
        new EventDispatcher($provider, new BootLogger(debug: false)),
    );

    $container = new Container();
    $passThrough = new class () implements Middleware {
        public function process(Request $request, callable $next): Response
        {
            return $next($request);
        }
    };
    foreach (['nonce', 'throttle'] as $alias) {
        $container->instance('corex.middleware.' . $alias, new class ($passThrough) {
            public function __construct(private readonly Middleware $middleware)
            {
            }

            public function __invoke(?string $parameter): Middleware
            {
                return $this->middleware;
            }
        });
    }

    $logger = new BootLogger(debug: false);

    return new SubmitController($service, new Pipeline($logger), new MiddlewareResolver($container, $logger));
}

beforeEach(function () {
    Functions\when('sanitize_key')->returnArg();
    Functions\when('sanitize_text_field')->alias(
        // WordPress returns '' for a non-scalar — the behaviour that lost multi-value fields.
        static fn (mixed $value): string => is_scalar($value) ? trim((string) $value) : ''
    );
    Functions\when('sanitize_textarea_field')->returnArg();
    Functions\when('sanitize_email')->returnArg();
});

it('keeps every value of a multi-select, rather than blanking the field', function () {
    $dispatched = [];

    sanitizingController($dispatched)->submit(briefRequest([
        'email' => 'a@b.com',
        'message' => 'Hi',
        'services' => ['video-editing', 'graphic-design'],
    ]));

    expect($dispatched)->toHaveCount(1)
        ->and($dispatched[0]->values['services'])->toBe(['video-editing', 'graphic-design']);
});

it('sanitizes each value of a multi-select individually', function () {
    $dispatched = [];

    sanitizingController($dispatched)->submit(briefRequest([
        'email' => 'a@b.com',
        'message' => 'Hi',
        'services' => ['  video-editing  ', 'graphic-design'],
    ]));

    expect($dispatched[0]->values['services'])->toBe(['video-editing', 'graphic-design']);
});

/*
 * ⚠ UPSTREAM BEHAVIOUR, not ours. Reported.
 *
 * Our fork rejected a scalar sent to a list-typed field with a 422, on the reasoning that it means a
 * spoofed or malformed payload. Upstream v0.40.0's `SubmitController::sanitizeList()` instead returns
 * `sanitize_text_field((string) $value)` for anything non-array, so the submission is accepted and the
 * field is stored as a STRING where every other submission of that field stores a list.
 *
 * We take upstream's version rather than re-forking `SubmitController` — carrying a fork edit on a file
 * upstream actively develops is what this whole update exists to stop doing. The case is kept, inverted,
 * so the behaviour is pinned and visible: if upstream tightens this later, this test goes red and we
 * find out deliberately rather than by noticing an odd row in the inbox.
 *
 * Impact is a data-shape inconsistency, not a vulnerability: the value is still sanitized, and Perego's
 * only multi-select (`services` on the project brief) renders a scalar readably.
 */
it('accepts a scalar for a list field and stores it as a string — upstream behaviour, reported', function () {
    $dispatched = [];

    $response = sanitizingController($dispatched)->submit(briefRequest([
        'email' => 'a@b.com',
        'message' => 'Hi',
        'services' => 'video-editing',
    ]));

    expect($response->get_status())->toBe(200)
        ->and($dispatched)->toHaveCount(1)
        ->and($dispatched[0]->values['services'])->toBe('video-editing');
});

it('still applies the scalar sanitizers and still drops undeclared keys', function () {
    $dispatched = [];

    sanitizingController($dispatched)->submit(briefRequest([
        'email' => 'a@b.com',
        'message' => 'Hi',
        'services' => ['video-editing'],
        'role' => 'administrator', // not a declared field
    ]));

    expect($dispatched[0]->values['email'])->toBe('a@b.com')
        ->and($dispatched[0]->values['message'])->toBe('Hi')
        ->and($dispatched[0]->values)->not->toHaveKey('role');
});
