# WP Admin Bar

Enhanced WordPress admin bar with extensible user information display. Shows user details, roles, and allows other plugins to add custom entity data like company information, branches, positions, and more.

## Features

- **Default User Information**: Displays basic WordPress user data (username, email, roles) without any configuration
- **Extensible Architecture**: Other plugins can easily enhance the admin bar with custom data via hooks
- **Performance Optimized**: Caching mechanism (5 minutes) reduces database queries
- **Responsive Design**: Adapts to different screen sizes, hides on mobile
- **Developer Friendly**: Well-documented hooks, filters, and helper methods
- **Standalone**: No dependencies on other plugins

## Installation

1. Upload the `wp-admin-bar` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The admin bar will automatically appear for logged-in users

## Basic Usage

Once activated, the plugin displays basic user information in the WordPress admin bar:

- User ID, Username, Email
- WordPress roles
- WordPress capabilities

**No configuration needed!** The plugin works out of the box.

## For Developers: Extending the Admin Bar

This plugin is designed to be extended by other plugins. Here's how to add custom data to the admin bar.

### 1. Add Entity Data via Filter

Use the `wp_admin_bar_user_data` filter to add custom entity information:

```php
add_filter('wp_admin_bar_user_data', function($data, $user_id, $user) {
    // Check if this user has entity data
    $customer_id = get_user_meta($user_id, 'customer_id', true);

    if (!$customer_id) {
        return $data; // No enhancement needed
    }

    // Get customer data from your plugin
    $customer = get_customer($customer_id); // Your custom function

    // Merge with existing data
    return array_merge($data, [
        'entity_name'  => $customer->company_name,
        'entity_code'  => $customer->customer_code,
        'entity_icon'  => '🏢',
        'branch_name'  => $customer->branch,
        'branch_code'  => $customer->branch_code,
        'position'     => $customer->position,
        'department'   => $customer->department,

        // Custom fields (any key-value pairs)
        'custom_fields' => [
            'Customer Type'     => $customer->type,
            'Registration Date' => $customer->registration_date,
            'Account Manager'   => $customer->account_manager,
        ],
    ]);
}, 10, 3);
```

### 2. Inject Custom Content into Dropdown

Use actions to add custom HTML sections to the dropdown:

```php
// Add content at the bottom of dropdown
add_action('wp_admin_bar_after_sections', function($user_id, $user, $data) {
    ?>
    <div class="info-section custom-subscription-section">
        <h4 class="section-title">Subscription Status</h4>
        <div class="section-content">
            <div class="info-item">
                <span class="info-label">Plan:</span>
                <span class="info-value">Premium</span>
            </div>
            <div class="info-item">
                <span class="info-label">Expires:</span>
                <span class="info-value">Dec 31, 2025</span>
            </div>

            <!-- Progress bar example -->
            <div class="wp-admin-bar-progress-container">
                <div class="wp-admin-bar-progress-bar" style="width: 75%"></div>
            </div>
        </div>
    </div>
    <?php
}, 10, 3);
```

### 3. Customize Role Names

Provide custom display names for roles:

```php
add_filter('wp_admin_bar_role_display_name', function($name, $role_slug) {
    $custom_roles = [
        'customer'       => 'Customer User',
        'customer_admin' => 'Customer Administrator',
        'vendor'         => 'Vendor Partner',
    ];

    return $custom_roles[$role_slug] ?? null;
}, 10, 2);
```

### 4. Customize Capability Names

Provide custom display names for capabilities:

```php
add_filter('wp_admin_bar_capability_display_name', function($name, $capability) {
    $custom_caps = [
        'view_reports'     => 'View Reports',
        'edit_products'    => 'Edit Products',
        'manage_inventory' => 'Manage Inventory',
    ];

    return $custom_caps[$capability] ?? null;
}, 10, 2);
```

### 5. Cache Invalidation

When user data changes, invalidate the cache:

```php
// When customer data is updated
add_action('customer_data_updated', function($customer_id) {
    // Get user ID associated with this customer
    $user_id = get_customer_user_id($customer_id);

    // Invalidate admin bar cache
    do_action('wp_admin_bar_invalidate_cache', $user_id);
});
```

### 6. Control Visibility

Control whether the admin bar should display for specific users:

```php
add_filter('wp_admin_bar_should_display', function($should_display, $user) {
    // Hide for specific roles
    if (in_array('subscriber', $user->roles)) {
        return false;
    }

    return $should_display;
}, 10, 2);
```

## Available Hooks & Filters

### Filters

#### `wp_admin_bar_user_data`
Enhance user data with custom entity information.

**Parameters:**
- `$data` (array) - Current user data
- `$user_id` (int) - WordPress user ID
- `$user` (WP_User) - WordPress user object

**Returns:** (array) Enhanced user data

---

#### `wp_admin_bar_display_text`
Customize the main admin bar item text.

**Parameters:**
- `$default_text` (string) - Default formatted text
- `$user` (WP_User) - WordPress user object
- `$data` (array) - User data array

**Returns:** (string) Custom display text

---

#### `wp_admin_bar_should_display`
Control whether to display the admin bar.

**Parameters:**
- `$should_display` (bool) - Whether to display
- `$user` (WP_User) - WordPress user object

**Returns:** (bool) True to display, false to hide

---

#### `wp_admin_bar_role_display_name`
Provide custom role name.

**Parameters:**
- `$name` (string|null) - Custom name or null
- `$role_slug` (string) - Role slug

**Returns:** (string|null) Custom name or null for default

---

#### `wp_admin_bar_capability_display_name`
Provide custom capability name.

**Parameters:**
- `$name` (string|null) - Custom name or null
- `$capability` (string) - Capability key

**Returns:** (string|null) Custom name or null for default

---

#### `wp_admin_bar_final_data`
Modify final user data before caching.

**Parameters:**
- `$data` (array) - Final user data
- `$user_id` (int) - WordPress user ID

**Returns:** (array) Modified data

---

#### `wp_admin_bar_cache_duration`
Customize cache duration in seconds.

**Parameters:**
- `$duration` (int) - Duration in seconds (default: 300)

**Returns:** (int) Custom duration

---

#### `wp_admin_bar_key_capabilities`
Filter which capabilities to display.

**Parameters:**
- `$capabilities` (array) - Capability names
- `$user_id` (int) - WordPress user ID
- `$data` (array) - User data

**Returns:** (array) Filtered capabilities

---

#### `wp_admin_bar_max_capabilities_display`
Maximum number of capabilities to display.

**Parameters:**
- `$max` (int) - Maximum number (default: 10)

**Returns:** (int) Custom maximum

---

### Actions

#### `wp_admin_bar_before_sections`
Inject content before all sections.

**Parameters:**
- `$user_id` (int) - WordPress user ID
- `$user` (WP_User) - WordPress user object
- `$data` (array) - User data

---

#### `wp_admin_bar_after_sections`
Inject content after all sections.

**Parameters:**
- `$user_id` (int) - WordPress user ID
- `$user` (WP_User) - WordPress user object
- `$data` (array) - User data

---

#### `wp_admin_bar_section_before_{section}`
Inject content before specific section.

Available sections:
- `user_info`
- `entity`
- `custom_fields`
- `roles`
- `capabilities`

**Parameters:**
- `$user_id` (int) - WordPress user ID
- `$user` (WP_User) - WordPress user object
- `$data` (array) - User data

---

#### `wp_admin_bar_section_after_{section}`
Inject content after specific section.

**Parameters:** Same as before hooks

---

#### `wp_admin_bar_invalidate_cache`
Invalidate user cache.

**Parameters:**
- `$user_id` (int) - WordPress user ID

---

#### `wp_admin_bar_initialized`
Fires after plugin initialization.

**Parameters:**
- `$plugin` (WP_Admin_Bar) - Main plugin instance

---

#### `wp_admin_bar_activated`
Fires when plugin is activated.

---

#### `wp_admin_bar_deactivated`
Fires when plugin is deactivated.

---

#### `wp_admin_bar_cache_invalidated`
Fires after cache is invalidated.

**Parameters:**
- `$user_id` (int) - WordPress user ID

---

## Data Structure

When enhancing user data via `wp_admin_bar_user_data` filter, you can include these fields:

### Default Fields (Always Present)
```php
[
    'user_id'      => 123,
    'username'     => 'john',
    'email'        => 'john@example.com',
    'display_name' => 'John Doe',
    'first_name'   => 'John',
    'last_name'    => 'Doe',
    'roles'        => ['customer'],
    'role_names'   => ['Customer'],
    'capabilities' => ['read' => true, ...],
    'capability_names' => ['View Reports', ...],
]
```

### Enhanced Fields (Optional - Added by Plugins)
```php
[
    // Entity information
    'entity_name'    => 'PT ABC Company',
    'entity_code'    => 'ABC123',
    'entity_icon'    => '🏢',

    // Branch information
    'branch_name'    => 'Jakarta Pusat',
    'branch_code'    => 'JKT01',
    'branch_type'    => 'Main Branch',

    // Division information
    'division_name'  => 'Technology',
    'division_type'  => 'Core Division',

    // Position information
    'position'       => 'Senior Manager',
    'department'     => 'IT Department',
    'relation_type'  => 'Employee',

    // Custom fields (flexible)
    'custom_fields'  => [
        'Employee ID'  => 'EMP001',
        'Location'     => 'Jakarta',
        'Level'        => 'Senior',
    ],
]
```

## JavaScript API

The plugin exposes a JavaScript object `WPAdminBar` with utility methods:

```javascript
// Check if dropdown is open
WPAdminBar.isDropdownOpen();

// Open dropdown
WPAdminBar.openDropdown();

// Close dropdown
WPAdminBar.closeDropdown();

// Toggle dropdown
WPAdminBar.toggleDropdown();

// Refresh user info (requires AJAX endpoint implementation)
WPAdminBar.refreshUserInfo();
```

### JavaScript Events

Listen to custom events:

```javascript
// Admin bar initialized
$(document).on('wpAdminBarInitialized', function() {
    console.log('Admin bar ready!');
});

// Before refresh
$(document).on('wpAdminBarBeforeRefresh', function() {
    console.log('Refreshing...');
});

// After refresh success
$(document).on('wpAdminBarRefreshSuccess', function(e, data) {
    console.log('Refresh successful!', data);
});

// Trigger refresh manually
$(document).trigger('wpAdminBarRefresh');
```

## Styling

The plugin includes comprehensive CSS. You can customize styles by adding to your theme:

```css
/* Customize entity color */
.wp-admin-bar-entity {
    color: #your-color !important;
}

/* Customize dropdown width */
.wp-admin-bar-dropdown-content {
    min-width: 400px !important;
}

/* Add custom section styling */
.my-custom-section {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 5px;
}
```

## Performance

- **Caching**: User data is cached for 5 minutes (configurable)
- **Cache Invalidation**: Automatic when user profile updated, or manual via action
- **Optimized Queries**: Data fetched once per page load (from cache)
- **Lazy Loading**: Assets only loaded when admin bar is showing

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Responsive Breakpoints

- **Desktop** (>960px): Full display
- **Tablet** (782-960px): Simplified (hide separator and roles)
- **Mobile** (<782px): Hidden completely

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## License

GPL v2 or later

## Support

For issues, questions, or contributions, please visit the GitHub repository.

## Changelog

### 1.0.0 - 2025-12-25
- Initial release
- Standalone plugin with extensible architecture
- Default WordPress user data display
- Hook system for plugin enhancements
- Responsive design
- Caching mechanism
- JavaScript API

## Credits

Developed with ❤️ for the WordPress community.
