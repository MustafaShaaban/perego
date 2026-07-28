<?php

/**
 * Base TestCase for headless unit tests.
 *
 * Sets up Brain Monkey so WordPress functions (add_action, get_option, …) can be
 * stubbed without loading WordPress — keeping the unit suite fast and runnable with
 * no optional plugins present (spec FR-022, SC-007).
 *
 * @package Corex\Tests\Unit
 */

declare(strict_types=1);

namespace Corex\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Translation is not behaviour any unit test is asserting, but any string a class decides to
        // make translatable pulls `__()` into its call path — and an unstubbed one fails the test for
        // a reason unrelated to what it covers. Returning the source string is what an untranslated
        // site does anyway; a test that cares about translation stubs it again with its own map.
        Monkey\Functions\when('__')->returnArg();
        Monkey\Functions\when('esc_html__')->returnArg();
        Monkey\Functions\when('esc_attr__')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
