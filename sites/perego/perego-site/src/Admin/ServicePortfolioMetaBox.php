<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Admin;

use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;

defined('ABSPATH') || exit;

/** A native Service editor for the selected-work query/manual/hybrid contract. */
final class ServicePortfolioMetaBox
{
    private const NONCE_ACTION = 'perego_service_portfolio_save';
    private const NONCE_FIELD = 'perego_service_portfolio_nonce';
    private const PROJECTS_FIELD = 'perego_service_portfolio_projects';
    private const EXCLUSIONS_FIELD = 'perego_service_portfolio_exclusions';

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addBox']);
        add_action('save_post', [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function addBox(): void
    {
        add_meta_box(
            'perego-service-portfolio',
            __('Selected work', 'perego-site'),
            function ($post): void {
                $this->render((int) $post->ID);
            },
            ServicePostType::POST_TYPE,
            'normal',
            'default',
        );
    }

    public function enqueue(string $hook): void
    {
        if (! in_array($hook, ['post.php', 'post-new.php'], true) || get_post_type() !== ServicePostType::POST_TYPE) {
            return;
        }
        wp_register_script('perego-service-portfolio-editor', false, [], null, true);
        wp_enqueue_script('perego-service-portfolio-editor');
        wp_add_inline_script('perego-service-portfolio-editor', 'window.PeregoServicePortfolio = ' . wp_json_encode([
            'slotLabel' => __('Masonry position %d', 'perego-site'),
        ]) . ';', 'before');
        wp_add_inline_script('perego-service-portfolio-editor', $this->script());
    }

    private function render(int $postId): void
    {
        $mode = ServicePostType::sanitizePortfolioMode(get_post_meta($postId, ServicePostType::META_PORTFOLIO_MODE, true));
        $selected = ServicePostType::sanitizeIntList(get_post_meta($postId, ServicePostType::META_PORTFOLIO_PROJECT_IDS, true));
        $excluded = ServicePostType::sanitizeIntList(get_post_meta($postId, ServicePostType::META_PORTFOLIO_EXCLUDE_IDS, true));
        $projects = $this->projects($selected);

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        echo '<div class="perego-service-portfolio">';
        echo '<p class="description">' . esc_html__('Automatic uses this Service’s Project category. Manual uses only your selected Projects. Hybrid places selected Projects first, then fills from the category.', 'perego-site') . '</p>';
        echo '<p><label for="perego-service-portfolio-mode">' . esc_html__('Source', 'perego-site') . '</label><select id="perego-service-portfolio-mode" name="' . esc_attr(ServicePostType::META_PORTFOLIO_MODE) . '">';
        foreach ([
            'automatic' => __('Automatic category results', 'perego-site'),
            'manual' => __('Manual selection only', 'perego-site'),
            'hybrid' => __('Manual selection first, then category results', 'perego-site'),
        ] as $value => $label) {
            printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($value), selected($mode, $value, false), esc_html($label));
        }
        echo '</select></p>';
        echo '<h4>' . esc_html__('Choose and order Projects', 'perego-site') . '</h4>';
        echo '<p class="description">' . esc_html__('Select Projects for Manual or Hybrid mode. Use the arrows to choose their visual order.', 'perego-site') . '</p>';
        echo '<ol id="perego-service-portfolio-projects">';
        foreach ($projects as $project) {
            $isSelected = in_array($project->ID, $selected, true);
            printf('<li><label><input type="checkbox" name="%1$s[]" value="%2$d"%3$s /> %4$s</label> <span class="perego-service-portfolio__slot" aria-live="polite">%5$s</span> <button type="button" class="button-link perego-service-portfolio__move" data-direction="up">%6$s</button> <button type="button" class="button-link perego-service-portfolio__move" data-direction="down">%7$s</button></li>', esc_attr(self::PROJECTS_FIELD), $project->ID, checked($isSelected, true, false), esc_html(get_the_title($project)), $isSelected ? esc_html(sprintf(__('Masonry position %d', 'perego-site'), array_search($project->ID, $selected, true) + 1)) : '', esc_html__('Up', 'perego-site'), esc_html__('Down', 'perego-site'));
        }
        echo '</ol>';
        echo '<details><summary>' . esc_html__('Exclude from automatic results', 'perego-site') . '</summary><p class="description">' . esc_html__('Exclusions apply in Automatic and Hybrid modes.', 'perego-site') . '</p><ul>';
        foreach ($projects as $project) {
            printf('<li><label><input type="checkbox" name="%1$s[]" value="%2$d"%3$s /> %4$s</label></li>', esc_attr(self::EXCLUSIONS_FIELD), $project->ID, checked(in_array($project->ID, $excluded, true), true, false), esc_html(get_the_title($project)));
        }
        echo '</ul></details></div>';
    }

    /** @param list<int> $selected @return list<\WP_Post> */
    private function projects(array $selected): array
    {
        $projects = get_posts([
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 120,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        $order = array_flip($selected);
        usort($projects, static fn (\WP_Post $left, \WP_Post $right): int => ($order[$left->ID] ?? PHP_INT_MAX) <=> ($order[$right->ID] ?? PHP_INT_MAX));

        return $projects;
    }

    /** @param int $postId @param \WP_Post $post */
    public function save($postId, $post): void
    {
        $postId = (int) $postId;
        if ((string) $post->post_type !== ServicePostType::POST_TYPE || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($postId)) {
            return;
        }
        $nonce = isset($_POST[self::NONCE_FIELD]) ? sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_FIELD])) : '';
        if ($nonce === '' || ! wp_verify_nonce($nonce, self::NONCE_ACTION) || ! current_user_can('edit_post', $postId)) {
            return;
        }

        $mode = ServicePostType::sanitizePortfolioMode(wp_unslash((string) ($_POST[ServicePostType::META_PORTFOLIO_MODE] ?? 'automatic')));
        update_post_meta($postId, ServicePostType::META_PORTFOLIO_MODE, $mode);
        $this->saveIds($postId, ServicePostType::META_PORTFOLIO_PROJECT_IDS, $_POST[self::PROJECTS_FIELD] ?? []);
        $this->saveIds($postId, ServicePostType::META_PORTFOLIO_EXCLUDE_IDS, $_POST[self::EXCLUSIONS_FIELD] ?? []);
    }

    /** @param mixed $raw */
    private function saveIds(int $postId, string $key, $raw): void
    {
        $ids = ServicePostType::sanitizeIntList(is_array($raw) ? wp_unslash($raw) : []);
        if ($ids === []) {
            delete_post_meta($postId, $key);
            return;
        }
        update_post_meta($postId, $key, $ids);
    }

    private function script(): string
    {
        return <<<'JS'
( function () {
	var labels = window.PeregoServicePortfolio || {};
	function updateSlots() {
		var position = 0;
		document.querySelectorAll( '#perego-service-portfolio-projects li' ).forEach( function ( item ) {
			var checkbox = item.querySelector( 'input[type="checkbox"]' );
			var slot = item.querySelector( '.perego-service-portfolio__slot' );
			if ( ! checkbox || ! slot ) { return; }
			if ( checkbox.checked ) { position += 1; slot.textContent = ( labels.slotLabel || 'Masonry position %d' ).replace( '%d', position ); } else { slot.textContent = ''; }
		} );
	}
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.perego-service-portfolio__move' );
		if ( ! button ) { return; }
		event.preventDefault();
		var item = button.closest( 'li' ), list = item && item.parentElement;
		if ( ! item || ! list ) { return; }
		if ( button.dataset.direction === 'up' && item.previousElementSibling ) { list.insertBefore( item, item.previousElementSibling ); }
		if ( button.dataset.direction === 'down' && item.nextElementSibling ) { list.insertBefore( item.nextElementSibling, item ); }
		updateSlots();
	} );
	document.addEventListener( 'change', function ( event ) {
		if ( event.target.closest( '#perego-service-portfolio-projects' ) ) { updateSlots(); }
	} );
	updateSlots();
}() );
JS;
    }
}
