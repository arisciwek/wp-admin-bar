<?php
/**
 * Admin Bar Dropdown Template
 *
 * Available variables:
 * @var int     $user_id WordPress user ID
 * @var WP_User $user    WordPress user object
 * @var array   $data    User data array (includes WordPress data + enhanced data from plugins)
 *
 * Data array may contain:
 * - user_id, username, email, display_name, first_name, last_name
 * - roles, role_names
 * - capabilities, capability_names
 * - entity_name, entity_code, entity_icon (if enhanced by plugins)
 * - branch_name, branch_code (if enhanced by plugins)
 * - position, department (if enhanced by plugins)
 * - custom_fields (array of key => value pairs from plugins)
 * - Any other fields added by plugins via wp_admin_bar_user_data filter
 *
 * @package WP_Admin_Bar
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wp-admin-bar-dropdown-content">

    <?php
    /**
     * Action: Before all sections
     *
     * Allows plugins to inject content at the top of the dropdown.
     *
     * @param int     $user_id WordPress user ID
     * @param WP_User $user    WordPress user object
     * @param array   $data    User data array
     */
    do_action('wp_admin_bar_before_sections', $user_id, $user, $data);
    ?>

    <?php
    /**
     * Section: User Information
     * Always displayed - shows basic WordPress user info
     */
    do_action('wp_admin_bar_section_before_user_info', $user_id, $user, $data);

    $user_info = array(
        'User ID'      => $data['user_id'],
        'Username'     => $data['username'],
        'Email'        => $data['email'],
        'Display Name' => $data['display_name'],
    );

    // Add first/last name if available
    if (!empty($data['first_name'])) {
        $user_info['First Name'] = $data['first_name'];
    }
    if (!empty($data['last_name'])) {
        $user_info['Last Name'] = $data['last_name'];
    }

    WP_Admin_Bar_Renderer::render_section('User Information', $user_info, 'user-info-section');

    do_action('wp_admin_bar_section_after_user_info', $user_id, $user, $data);
    ?>

    <?php
    /**
     * Section: Entity Information
     * Conditional - only shows if entity data exists (enhanced by plugins)
     */
    if (!empty($data['entity_name']) || !empty($data['entity_code']) || !empty($data['branch_name']) || !empty($data['position'])) :
        do_action('wp_admin_bar_section_before_entity', $user_id, $user, $data);

        $entity_info = array();

        if (!empty($data['entity_name'])) {
            $entity_info['Entity Name'] = $data['entity_name'];
        }
        if (!empty($data['entity_code'])) {
            $entity_info['Entity Code'] = $data['entity_code'];
        }
        if (!empty($data['branch_name'])) {
            $entity_info['Branch'] = $data['branch_name'];
        }
        if (!empty($data['branch_code'])) {
            $entity_info['Branch Code'] = $data['branch_code'];
        }
        if (!empty($data['branch_type'])) {
            $entity_info['Branch Type'] = $data['branch_type'];
        }
        if (!empty($data['division_name'])) {
            $entity_info['Division'] = $data['division_name'];
        }
        if (!empty($data['division_type'])) {
            $entity_info['Division Type'] = $data['division_type'];
        }
        if (!empty($data['position'])) {
            $entity_info['Position'] = $data['position'];
        }
        if (!empty($data['department'])) {
            $entity_info['Department'] = $data['department'];
        }
        if (!empty($data['relation_type'])) {
            $entity_info['Relation Type'] = $data['relation_type'];
        }

        if (!empty($entity_info)) {
            WP_Admin_Bar_Renderer::render_section('Entity Information', $entity_info, 'entity-info-section');
        }

        do_action('wp_admin_bar_section_after_entity', $user_id, $user, $data);
    endif;
    ?>

    <?php
    /**
     * Section: Custom Fields
     * Conditional - only shows if custom_fields array exists
     * Plugins can add any custom key-value pairs here
     */
    if (!empty($data['custom_fields']) && is_array($data['custom_fields'])) :
        do_action('wp_admin_bar_section_before_custom_fields', $user_id, $user, $data);

        WP_Admin_Bar_Renderer::render_section('Additional Information', $data['custom_fields'], 'custom-fields-section');

        do_action('wp_admin_bar_section_after_custom_fields', $user_id, $user, $data);
    endif;
    ?>

    <?php
    /**
     * Section: Roles
     * Always displayed - shows WordPress roles
     */
    if (!empty($data['role_names'])) :
        do_action('wp_admin_bar_section_before_roles', $user_id, $user, $data);

        WP_Admin_Bar_Renderer::render_list('Roles', $data['role_names'], 'roles-section');

        do_action('wp_admin_bar_section_after_roles', $user_id, $user, $data);
    endif;
    ?>

    <?php
    /**
     * Section: Key Capabilities
     * Conditional - shows if capability_names exist
     */
    if (!empty($data['capability_names'])) :
        do_action('wp_admin_bar_section_before_capabilities', $user_id, $user, $data);

        // Allow filtering which capabilities to display
        $capabilities_to_display = apply_filters('wp_admin_bar_key_capabilities', $data['capability_names'], $user_id, $data);

        if (!empty($capabilities_to_display)) {
            // Limit to reasonable number (e.g., first 10)
            $max_capabilities = apply_filters('wp_admin_bar_max_capabilities_display', 10);
            if (count($capabilities_to_display) > $max_capabilities) {
                $capabilities_to_display = array_slice($capabilities_to_display, 0, $max_capabilities);
                $capabilities_to_display[] = '... and ' . (count($data['capability_names']) - $max_capabilities) . ' more';
            }

            WP_Admin_Bar_Renderer::render_list('Key Capabilities', $capabilities_to_display, 'capabilities-section');
        }

        do_action('wp_admin_bar_section_after_capabilities', $user_id, $user, $data);
    endif;
    ?>

    <?php
    /**
     * Action: After all sections
     *
     * Allows plugins to inject content at the bottom of the dropdown.
     * This is useful for adding custom sections like:
     * - Subscription status
     * - Completeness progress bars
     * - Quick links
     * - Statistics
     * - etc.
     *
     * @param int     $user_id WordPress user ID
     * @param WP_User $user    WordPress user object
     * @param array   $data    User data array
     */
    do_action('wp_admin_bar_after_sections', $user_id, $user, $data);
    ?>

</div>
