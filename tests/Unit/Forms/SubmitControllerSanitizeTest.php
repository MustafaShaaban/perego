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

it('rejects a non-array multi-select value instead of smuggling it through as a string', function () {
    $dispatched = [];

    // A scalar here means a spoofed or malformed payload. It sanitizes to an empty list, which
    // `required` then rejects — the honest outcome for a list-typed field.
    $response = sanitizingController($dispatched)->submit(briefRequest([
        'email' => 'a@b.com',
        'message' => 'Hi',
        'services' => 'video-editing',
    ]));

    expect($response->get_status())->toBe(422)
        ->and($dispatched)->toBe([]);
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
