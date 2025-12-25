/**
 * WP Admin Bar JavaScript
 *
 * Handles client-side functionality for the admin bar dropdown.
 *
 * @package WP_Admin_Bar
 */

(function($) {
    'use strict';

    /**
     * WP Admin Bar namespace
     */
    var WPAdminBar = {

        /**
         * Initialize the admin bar functionality
         */
        init: function() {
            this.setupDropdown();
            this.setupCollapsible();
            this.bindEvents();

            /**
             * Custom event: Admin bar initialized
             *
             * Plugins can listen to this event to run custom code
             * after the admin bar is ready.
             *
             * Example:
             * $(document).on('wpAdminBarInitialized', function() {
             *     console.log('Admin bar ready!');
             * });
             */
            $(document).trigger('wpAdminBarInitialized');
        },

        /**
         * Setup dropdown behavior
         *
         * Prevents the dropdown from closing when clicking inside it.
         * This allows users to interact with custom content (buttons, links, etc.)
         * without the dropdown disappearing.
         */
        setupDropdown: function() {
            var $dropdown = $('#wp-admin-bar-wp-admin-bar-info');

            if ($dropdown.length === 0) {
                return;
            }

            // Prevent dropdown from closing when clicking inside the dropdown content
            $dropdown.on('click', '.wp-admin-bar-dropdown-content', function(e) {
                e.stopPropagation();
                e.preventDefault();
            });

            // Prevent dropdown from closing when clicking on submenu items
            $dropdown.on('click', '.ab-submenu', function(e) {
                e.stopPropagation();
            });

            // Prevent dropdown item from acting like a link
            $dropdown.on('click', '#wp-admin-bar-wp-admin-bar-info-details > .ab-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });

            // Allow text selection without closing dropdown
            $dropdown.on('mousedown mouseup', '.wp-admin-bar-dropdown-content', function(e) {
                e.stopPropagation();
            });

            // Allow links and buttons to work normally (if plugins add them)
            $dropdown.on('click', 'a[href]:not([href="javascript:void(0)"]), button', function(e) {
                // Let the default action happen (navigation, etc.)
                // Only stop propagation to prevent dropdown from closing
                e.stopPropagation();
            });
        },

        /**
         * Setup collapsible sections
         *
         * Handles toggle functionality for collapsible sections like Key Capabilities
         */
        setupCollapsible: function() {
            var $dropdown = $('#wp-admin-bar-wp-admin-bar-info');

            if ($dropdown.length === 0) {
                return;
            }

            // Handle collapsible header clicks
            $dropdown.on('click', '.collapsible-header', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $header = $(this);
                var targetId = $header.data('toggle');
                var $content = $('#' + targetId);

                // Toggle active class on header
                $header.toggleClass('active');

                // Toggle content visibility
                if ($content.hasClass('active')) {
                    $content.removeClass('active');
                    $content.css('display', 'none');
                } else {
                    $content.addClass('active');
                    $content.css('display', 'block');
                }
            });
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;

            // Refresh button (if added by plugins)
            $(document).on('click', '.wp-admin-bar-refresh', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.refreshUserInfo();
            });

            /**
             * Custom event: Refresh user info
             *
             * Plugins can trigger this event to force a refresh of the admin bar.
             *
             * Example:
             * $(document).trigger('wpAdminBarRefresh');
             */
            $(document).on('wpAdminBarRefresh', function() {
                self.refreshUserInfo();
            });
        },

        /**
         * Refresh user information via AJAX
         *
         * This method can be used by plugins to refresh the admin bar
         * data without reloading the page. Currently a placeholder for
         * future implementation.
         *
         * To implement, you would need to:
         * 1. Create an AJAX endpoint in PHP
         * 2. Return updated user data
         * 3. Replace the dropdown content
         */
        refreshUserInfo: function() {
            var $dropdown = $('#wp-admin-bar-wp-admin-bar-info-details .ab-item');

            if ($dropdown.length === 0) {
                return;
            }

            // Show loading state
            this.showLoading($dropdown);

            /**
             * Custom event: Before refresh
             *
             * Allows plugins to perform actions before refresh.
             */
            $(document).trigger('wpAdminBarBeforeRefresh');

            // AJAX request to refresh data
            $.ajax({
                url: wpAdminBarData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_admin_bar_refresh',
                    nonce: wpAdminBarData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $dropdown.html(response.data.html);

                        /**
                         * Custom event: After refresh success
                         */
                        $(document).trigger('wpAdminBarRefreshSuccess', [response.data]);
                    } else {
                        console.error('WP Admin Bar: Refresh failed', response);

                        /**
                         * Custom event: After refresh error
                         */
                        $(document).trigger('wpAdminBarRefreshError', [response]);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('WP Admin Bar: AJAX error', error);
                    $(document).trigger('wpAdminBarRefreshError', [error]);
                },
                complete: function() {
                    /**
                     * Custom event: After refresh complete
                     */
                    $(document).trigger('wpAdminBarRefreshComplete');
                }
            });
        },

        /**
         * Show loading state in dropdown
         *
         * @param {jQuery} $element Element to show loading in
         */
        showLoading: function($element) {
            var loadingHTML = '<div class="wp-admin-bar-loading">' +
                '<span class="wp-admin-bar-sr-only">Loading...</span>' +
                'Refreshing user information...' +
                '</div>';

            $element.html(loadingHTML);
        },

        /**
         * Utility: Get current user ID from admin bar
         *
         * @return {number|null} User ID or null
         */
        getCurrentUserId: function() {
            var $userInfo = $('.wp-admin-bar-dropdown-content');
            if ($userInfo.length > 0) {
                // Try to extract user ID from data attribute or content
                var userId = $userInfo.data('user-id');
                if (userId) {
                    return parseInt(userId, 10);
                }
            }
            return null;
        },

        /**
         * Utility: Check if dropdown is open
         *
         * @return {boolean} True if open
         */
        isDropdownOpen: function() {
            return $('#wp-admin-bar-wp-admin-bar-info').hasClass('hover');
        },

        /**
         * Utility: Open dropdown
         */
        openDropdown: function() {
            $('#wp-admin-bar-wp-admin-bar-info').addClass('hover');
        },

        /**
         * Utility: Close dropdown
         */
        closeDropdown: function() {
            $('#wp-admin-bar-wp-admin-bar-info').removeClass('hover');
        },

        /**
         * Utility: Toggle dropdown
         */
        toggleDropdown: function() {
            if (this.isDropdownOpen()) {
                this.closeDropdown();
            } else {
                this.openDropdown();
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        WPAdminBar.init();
    });

    /**
     * Expose to global scope for plugins to use
     */
    window.WPAdminBar = WPAdminBar;

})(jQuery);
