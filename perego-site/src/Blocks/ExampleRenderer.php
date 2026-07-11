<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Services\ExampleService;

/**
 * Server-renders the perego-theme/example block — escaped, i18n-ready, token-styled,
 * RTL-correct. Part of the --starter example; delete it per REMOVE-EXAMPLE.md.
 */
final class ExampleRenderer
{
    public function __construct(private readonly ExampleService $service)
    {
    }

    public function render(): string
    {
        $items = $this->service->all();

        if ($items === []) {
            return '<p class="perego-theme-example">'
                . esc_html__('No examples yet.', 'perego-site')
                . '</p>';
        }

        $list = '';
        foreach ($items as $item) {
            $list .= '<li>' . esc_html($item->title) . '</li>';
        }

        return '<ul class="perego-theme-example">' . $list . '</ul>';
    }
}
