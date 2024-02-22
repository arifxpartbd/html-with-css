<?php
/*
Plugin Name: Noticeboard
Description: A simple noticeboard plugin for WordPress.
Version: 1.0
Author: Debug city
*/

// Create custom database table on plugin activation
function noticeboard_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notices';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text NOT NULL,
        date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'noticeboard_create_table');

// Add notice form in admin dashboard
function noticeboard_add_notice_form() {
    ?>
    <div class="wrap">
        <h1>Add New Notice</h1>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="noticeboard_add_notice">
            <label for="notice_title">Title:</label><br>
            <input type="text" id="notice_title" name="notice_title"><br>
            <label for="notice_description">Description:</label><br>
            <textarea id="notice_description" name="notice_description"></textarea><br>
            <input type="submit" value="Add Notice">
        </form>
    </div>
    <?php
}

// Handle notice form submission
function noticeboard_handle_notice_submission() {
    if (isset($_POST['notice_title']) && isset($_POST['notice_description'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'notices';
        $title = sanitize_text_field($_POST['notice_title']);
        $description = sanitize_textarea_field($_POST['notice_description']);
        $wpdb->insert($table_name, array(
            'title' => $title,
            'description' => $description
        ));
    }
    wp_redirect(admin_url('admin.php?page=noticeboard'));
    exit;
}
add_action('admin_post_noticeboard_add_notice', 'noticeboard_handle_notice_submission');

// Display noticeboard in frontend
function noticeboard_display() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'notices';
    $notices = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");
    ?>
    <div class="noticeboard">
        <?php foreach ($notices as $notice): ?>
            <div class="notice">
                <h3><?php echo $notice->title; ?></h3>
                <p><?php echo substr($notice->description, 0, 100); ?>...</p>
                <a href="<?php echo admin_url('admin.php?page=noticeboard&notice_id=' . $notice->id); ?>" target="_blank">View</a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
add_shortcode('noticeboard', 'noticeboard_display');

// Add notice board menu item to the dashboard
function noticeboard_add_menu_item() {
    add_menu_page(
        'Notice Board',         // Page title
        'Notice Board',         // Menu title
        'manage_options',       // Capability required to access the menu
        'noticeboard',          // Menu slug
        'noticeboard_admin_page'// Callback function to display the admin page
    );
}
add_action('admin_menu', 'noticeboard_add_menu_item');

// Display the admin page for managing notices
function noticeboard_admin_page() {
    // Check if user has permissions to access the page
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Display the admin page content here
    noticeboard_add_notice_form();
}

// Define the noticeboard widget class
class Noticeboard_Widget extends WP_Widget {
    
    // Constructor
    public function __construct() {
        parent::__construct(
            'noticeboard_widget',
            'Noticeboard Widget',
            array( 'description' => 'Display notices in the sidebar' )
        );
    }
    
    // Widget frontend (display notices)
    public function widget( $args, $instance ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'notices';
        $notices = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");
        
        echo $args['before_widget'];
        echo '<div class="noticeboard-widget">';
        echo '<h2 class="widget-title">Notices</h2>';
        echo '<ul>';
        foreach ($notices as $notice) {
            echo '<li><a href="#" class="notice-link" data-notice-id="' . $notice->id . '">' . $notice->title . '</a></li>';
        }
        echo '</ul>';
        echo '</div>';
        echo $args['after_widget'];
        ?>
        <script>
        // JavaScript to handle notice link clicks
        document.addEventListener('DOMContentLoaded', function() {
            var noticeLinks = document.querySelectorAll('.notice-link');
            noticeLinks.forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault();
                    var noticeId = this.getAttribute('data-notice-id');
                    window.location.href = '<?php echo admin_url("admin.php?page=noticeboard&notice_id="); ?>' + noticeId;
                });
            });
        });
        </script>
        <?php
    }
}

// Register the noticeboard widget
function register_noticeboard_widget() {
    register_widget( 'Noticeboard_Widget' );
}
add_action( 'widgets_init', 'register_noticeboard_widget' );

