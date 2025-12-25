<?php
/**
 * WP Admin Bar Core Class
 *
 * Handles data gathering, caching, and business logic for the admin bar.
 *
 * @package WP_Admin_Bar
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core functionality for WP Admin Bar
 *
 * This class handles:
 * - Getting user data (WordPress + enhanced data from other plugins)
 * - Caching user data for performance
 * - Cache invalidation
 * - Helper methods for formatting role/capability names
 */
class WP_Admin_Bar_Core {

    /**
     * Cache duration in seconds (5 minutes)
     *
     * @var int
     */
    private $cache_duration = 300;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Cache invalidation hook
        add_action('wp_admin_bar_invalidate_cache', array($this, 'invalidate_user_cache'));

        // Also invalidate on profile update
        add_action('profile_update', array($this, 'invalidate_user_cache'));
        add_action('user_register', array($this, 'invalidate_user_cache'));

        // Allow filtering cache duration
        $this->cache_duration = apply_filters('wp_admin_bar_cache_duration', $this->cache_duration);
    }

    /**
     * Get user data for admin bar display
     *
     * This method:
     * 1. Checks cache first
     * 2. Gets default WordPress user data
     * 3. Allows other plugins to enhance the data via filter
     * 4. Caches the result
     * 5. Returns the final data array
     *
     * @param int $user_id WordPress user ID
     * @return array|null User data array or null if user not found
     */
    public function get_user_data($user_id) {
        // Check cache first
        $cache_key = $this->get_cache_key($user_id);
        $cached_data = get_transient($cache_key);

        if (false !== $cached_data) {
            return $cached_data;
        }

        // Get WordPress user
        $user = get_userdata($user_id);
        if (!$user) {
            return null;
        }

        // Build default data
        $data = $this->get_default_user_data($user);

        /**
         * Filter: Allow other plugins to enhance user data
         *
         * This is the main extension point for other plugins to add
         * custom entity data, branch info, positions, custom fields, etc.
         *
         * Multiple plugins can hook into this filter and merge their data.
         *
         * @param array   $data    Current user data array
         * @param int     $user_id WordPress user ID
         * @param WP_User $user    WordPress user object
         * @return array Enhanced user data
         */
        $data = apply_filters('wp_admin_bar_user_data', $data, $user_id, $user);

        // Format role names if not already provided
        if (empty($data['role_names']) && !empty($data['roles'])) {
            $data['role_names'] = $this->get_role_display_names($data['roles']);
        }

        // Format capability names if capabilities provided but no names
        if (!empty($data['capabilities']) && empty($data['capability_names'])) {
            $data['capability_names'] = $this->get_capability_display_names(
                array_keys(array_filter($data['capabilities']))
            );
        }

        /**
         * Filter: Final user data before caching
         *
         * This filter runs after all enhancements and formatting.
         * Use this if you need to modify the final data structure.
         *
         * @param array $data    Final user data array
         * @param int   $user_id WordPress user ID
         * @return array Modified user data
         */
        $data = apply_filters('wp_admin_bar_final_data', $data, $user_id);

        // Cache the data
        set_transient($cache_key, $data, $this->cache_duration);

        return $data;
    }

    /**
     * Get default WordPress user data
     *
     * @param WP_User $user WordPress user object
     * @return array Default user data
     */
    private function get_default_user_data($user) {
        return array(
            'user_id'      => $user->ID,
            'username'     => $user->user_login,
            'email'        => $user->user_email,
            'display_name' => $user->display_name,
            'first_name'   => $user->first_name,
            'last_name'    => $user->last_name,
            'roles'        => $user->roles,
            'role_names'   => array(),
            'capabilities' => $user->allcaps,
            'capability_names' => array(),
        );
    }

    /**
     * Get display names for role slugs
     *
     * Converts role slugs (e.g., 'administrator') to display names (e.g., 'Administrator')
     *
     * @param array $role_slugs Array of role slugs
     * @return array Array of display names
     */
    public function get_role_display_names($role_slugs) {
        $display_names = array();

        foreach ($role_slugs as $slug) {
            /**
             * Filter: Custom role display name
             *
             * Allows plugins to provide custom names for roles.
             * Return null to use default WordPress role name.
             *
             * @param string|null $name Custom name or null
             * @param string      $slug Role slug
             * @return string|null
             */
            $custom_name = apply_filters('wp_admin_bar_role_display_name', null, $slug);

            if (null !== $custom_name) {
                $display_names[] = $custom_name;
                continue;
            }

            // Try to get from WordPress roles
            $wp_roles = wp_roles();
            if (isset($wp_roles->role_names[$slug])) {
                $display_names[] = translate_user_role($wp_roles->role_names[$slug]);
            } else {
                // Fallback: humanize the slug
                $display_names[] = $this->humanize_string($slug);
            }
        }

        return $display_names;
    }

    /**
     * Get display names for capabilities
     *
     * Filters and formats capability names for display
     *
     * @param array $capabilities Array of capability keys
     * @return array Array of display names
     */
    public function get_capability_display_names($capabilities) {
        $display_names = array();

        // Get role slugs to filter out
        global $wp_roles;
        $role_slugs = array_keys($wp_roles->roles);

        // Core capabilities to skip
        $skip_caps = array('read', 'level_0', 'level_1', 'level_2', 'level_3', 'level_4', 'level_5', 'level_6', 'level_7', 'level_8', 'level_9', 'level_10');

        foreach ($capabilities as $cap) {
            // Skip role slugs
            if (in_array($cap, $role_slugs, true)) {
                continue;
            }

            // Skip core capabilities
            if (in_array($cap, $skip_caps, true)) {
                continue;
            }

            /**
             * Filter: Custom capability display name
             *
             * Allows plugins to provide custom names for capabilities.
             * Return null to use default humanized name.
             *
             * @param string|null $name Custom name or null
             * @param string      $cap  Capability key
             * @return string|null
             */
            $custom_name = apply_filters('wp_admin_bar_capability_display_name', null, $cap);

            if (null !== $custom_name) {
                $display_names[] = $custom_name;
            } else {
                $display_names[] = $this->humanize_string($cap);
            }
        }

        return $display_names;
    }

    /**
     * Humanize a string (convert slug to readable text)
     *
     * Examples:
     * - 'customer_admin' -> 'Customer Admin'
     * - 'edit_posts' -> 'Edit Posts'
     *
     * @param string $string Input string
     * @return string Humanized string
     */
    private function humanize_string($string) {
        // Replace underscores and hyphens with spaces
        $string = str_replace(array('_', '-'), ' ', $string);

        // Capitalize each word
        $string = ucwords($string);

        return $string;
    }

    /**
     * Check if admin bar should be displayed for user
     *
     * @param WP_User $user WordPress user object
     * @return bool True if should display, false otherwise
     */
    public function should_display_admin_bar($user) {
        // Default: show if user is logged in and admin bar is showing
        $should_display = is_user_logged_in() && is_admin_bar_showing();

        /**
         * Filter: Control admin bar display
         *
         * Allows plugins to override the display decision.
         *
         * @param bool    $should_display Whether to display admin bar
         * @param WP_User $user           WordPress user object
         * @return bool
         */
        return apply_filters('wp_admin_bar_should_display', $should_display, $user);
    }

    /**
     * Get cache key for user
     *
     * @param int $user_id WordPress user ID
     * @return string Cache key
     */
    private function get_cache_key($user_id) {
        return 'wp_admin_bar_user_' . $user_id;
    }

    /**
     * Invalidate cached data for a user
     *
     * @param int $user_id WordPress user ID
     */
    public function invalidate_user_cache($user_id) {
        $cache_key = $this->get_cache_key($user_id);
        delete_transient($cache_key);

        /**
         * Action: Fires after user cache is invalidated
         *
         * @param int $user_id WordPress user ID
         */
        do_action('wp_admin_bar_cache_invalidated', $user_id);
    }

    /**
     * Get formatted display text for main admin bar item
     *
     * @param WP_User $user WordPress user object
     * @param array   $data User data array
     * @return string Display text
     */
    public function get_display_text($user, $data) {
        // Display only roles (green text) - WordPress already shows user account info
        $roles = implode(', ', $data['role_names']);

        // Simple format: just show roles with icon
        $default_text = sprintf(
            '<span class="wp-admin-bar-roles">👤 %s</span>',
            esc_html($roles)
        );

        /**
         * Filter: Customize main admin bar display text
         *
         * @param string  $default_text Default formatted text
         * @param WP_User $user         WordPress user object
         * @param array   $data         User data array
         * @return string Custom display text
         */
        return apply_filters('wp_admin_bar_display_text', $default_text, $user, $data);
    }
}
