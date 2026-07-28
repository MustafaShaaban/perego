<?php

/**
 * Shared PHPUnit/Pest bootstrap for the Corex test suite.
 *
 * Loads the monorepo's single authoritative Composer autoloader. Per-suite setup
 * (Brain Monkey for Unit, WordPress for Integration) lives in the base TestCase
 * classes wired through tests/Pest.php — PHPUnit allows only one global bootstrap.
 *
 * @package Corex\Tests
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Corex src class files carry a `defined('ABSPATH') || exit;` direct-access guard
// (WooCommerce convention). Define it here so PSR-4 classes load in the headless
// suite without a WordPress runtime. See DECISIONS #20.
if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// Standard WordPress time constant, used by pure services (e.g. the Data trend window).
if (! defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

// Minimal REST stubs, same rationale as WP_Post below: a controller should keep WordPress's own
// `WP_REST_Request`/`WP_REST_Response` signatures rather than weaken them to an interface just to be
// testable, so the headless suite supplies the shapes instead. Only the members Corex controllers
// actually touch. The Integration suite boots real WordPress and gets the real classes.
if (! class_exists('WP_REST_Request')) {
    class WP_REST_Request implements ArrayAccess
    {
        /** @var array<string,mixed> */
        private array $bodyParams = [];

        /** @var array<string,mixed> */
        private array $urlParams = [];

        /** @var array<string,string> */
        private array $headers = [];

        /** @var array<string,mixed>|null */
        private ?array $jsonParams = null;

        public function __construct(private string $method = 'GET', private string $route = '')
        {
        }

        /** @param array<string,mixed> $params */
        public function set_body_params(array $params): void
        {
            $this->bodyParams = $params;
        }

        /** @return array<string,mixed> */
        public function get_body_params(): array
        {
            return $this->bodyParams;
        }

        /** @param array<string,mixed> $params */
        public function set_json_params(array $params): void
        {
            $this->jsonParams = $params;
        }

        /** @return array<string,mixed>|null */
        public function get_json_params(): ?array
        {
            return $this->jsonParams;
        }

        /** @param array<string,mixed> $params */
        public function set_url_params(array $params): void
        {
            $this->urlParams = $params;
        }

        public function set_header(string $name, string $value): void
        {
            $this->headers[strtolower($name)] = $value;
        }

        public function get_header(string $name): ?string
        {
            return $this->headers[strtolower($name)] ?? null;
        }

        public function get_param(string $key): mixed
        {
            return $this->urlParams[$key] ?? $this->bodyParams[$key] ?? null;
        }

        public function get_method(): string
        {
            return $this->method;
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
            $this->bodyParams[(string) $offset] = $value;
        }

        public function offsetUnset(mixed $offset): void
        {
            unset($this->bodyParams[(string) $offset]);
        }
    }
}

if (! class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public function __construct(private mixed $data = null, private int $status = 200)
        {
        }

        public function get_data(): mixed
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }
    }
}

// Minimal WP_Post stub so boundary code that type-checks `instanceof \WP_Post` (e.g. the kit page
// adopt path, spec 041) stays WP-idiomatic yet runnable headlessly. Only the fields the suite reads.
if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;

        /** @param array<string,mixed> $fields */
        public function __construct(array $fields = [])
        {
            foreach ($fields as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}
