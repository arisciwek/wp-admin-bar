<?php
/**
 * WP Admin Bar Renderer Class
 *
 * Handles rendering and integration with WordPress admin bar.
 *
 * @package WP_Admin_Bar
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderer for WP Admin Bar
 *
 * This class handles:
 * - Integration with WordPress admin_bar_menu hook
 * - Adding admin bar nodes
 * - Rendering dropdown content
 */
class WP_Admin_Bar_Renderer {

    /**
     * Core instance
     *
     * @var WP_Admin_Bar_Core
     */
    private $core;

    /**
     * Constructor
     *
     * @param WP_Admin_Bar_Core $core Core instance
     */
    public function __construct($core) {
        $this->core = $core;
    }

    /**
     * Initialize renderer
     */
    public function init() {
        // Hook into WordPress admin bar
        add_action('admin_bar_menu', array($this, 'add_admin_bar_items'), 100);
    }

    /**
     * Add items to WordPress admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar WordPress admin bar object
     */
    public function add_admin_bar_items($wp_admin_bar) {
        // Only show for logged in users
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        // Check if should display
        if (!$this->core->should_display_admin_bar($user)) {
            return;
        }

        // Get user data
        $data = $this->core->get_user_data($user_id);
        if (!$data) {
            return;
        }

        // Get display text for main item
        $display_text = $this->core->get_display_text($user, $data);

        // Add main admin bar item
        $wp_admin_bar->add_node(array(
            'id'     => 'wp-admin-bar-info',
            'parent' => 'top-secondary',
            'title'  => $display_text,
            'href'   => false,
            'meta'   => array(
                'class' => 'wp-admin-bar-info-item',
            ),
        ));

        // Add dropdown submenu
        $dropdown_content = $this->render_dropdown($user_id, $user, $data);

        $wp_admin_bar->add_node(array(
            'id'     => 'wp-admin-bar-info-details',
            'parent' => 'wp-admin-bar-info',
            'title'  => $dropdown_content,
            'href'   => false,
            'meta'   => array(
                'class' => 'wp-admin-bar-info-dropdown',
            ),
        ));
    }

    /**
     * Render dropdown content
     *
     * @param int     $user_id WordPress user ID
     * @param WP_User $user    WordPress user object
     * @param array   $data    User data array
     * @return string HTML content
     */
    private function render_dropdown($user_id, $user, $data) {
        // Start output buffering
        ob_start();

        // Load template
        $template_path = WP_ADMIN_BAR_PLUGIN_DIR . 'templates/dropdown.php';

        if (file_exists($template_path)) {
            // Make variables available to template
            include $template_path;
        } else {
            // Fallback if template not found
            echo '<div class="wp-admin-bar-dropdown-content">';
            echo '<p>Template not found</p>';
            echo '</div>';
        }

        // Get buffered content
        $content = ob_get_clean();

        return $content;
    }

    /**
     * Render a section in the dropdown
     *
     * Helper method for template to render consistent sections
     *
     * @param string $title   Section title
     * @param array  $items   Array of key => value items
     * @param string $classes Additional CSS classes
     */
    public static function render_section($title, $items, $classes = '') {
        if (empty($items)) {
            return;
        }

        $section_class = 'info-section';
        if ($classes) {
            $section_class .= ' ' . esc_attr($classes);
        }
        ?>
        <div class="<?php echo $section_class; ?>">
            <?php if ($title) : ?>
                <h4 class="section-title"><?php echo esc_html($title); ?></h4>
            <?php endif; ?>
            <div class="section-content">
                <?php foreach ($items as $key => $value) : ?>
                    <?php if (!empty($value)) : ?>
                        <div class="info-item">
                            <span class="info-label"><?php echo esc_html($key); ?>:</span>
                            <span class="info-value"><?php echo esc_html($value); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render a list in the dropdown
     *
     * Helper method for template to render lists (roles, capabilities, etc.)
     *
     * @param string $title   Section title
     * @param array  $items   Array of items
     * @param string $classes Additional CSS classes
     */
    public static function render_list($title, $items, $classes = '') {
        if (empty($items)) {
            return;
        }

        $section_class = 'info-section info-list';
        if ($classes) {
            $section_class .= ' ' . esc_attr($classes);
        }
        ?>
        <div class="<?php echo $section_class; ?>">
            <?php if ($title) : ?>
                <h4 class="section-title"><?php echo esc_html($title); ?></h4>
            <?php endif; ?>
            <div class="section-content">
                <ul class="info-items">
                    <?php foreach ($items as $item) : ?>
                        <li class="info-item"><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
    }
}
