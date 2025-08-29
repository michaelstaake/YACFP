<?php
/**
 * Plugin Name: Yet Another Contact Form Plugin
 * Description: YACFP
 * Version: 2025.08.28.01
 * Author: Michael Staake
 * Author URI: https://michaelstaake.com
 * License: GPL-3.0
 * Text Domain: yacfp
 */
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
define('YACFP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YACFP_PLUGIN_URL', plugin_dir_url(__FILE__));
// Load text domain for translations
function yacfp_load_textdomain() {
    load_plugin_textdomain('yacfp', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('init', 'yacfp_load_textdomain');
// Activation hook to create submissions table
function yacfp_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'yacfp_submissions';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        submitted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        form_data longtext NOT NULL,
        referrer varchar(255) DEFAULT '' NOT NULL,
        user_ip varchar(45) DEFAULT '' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    // Set default settings
    if (!get_option('yacfp_settings')) {
        $default_settings = [
            'captcha_type' => 'disabled',
            'theme' => 'default.css',
            'recipient' => get_option('admin_email'),
        ];
        update_option('yacfp_settings', $default_settings);
    }
}
register_activation_hook(__FILE__, 'yacfp_activate');
// Admin menu
function yacfp_admin_menu() {
    add_submenu_page(
        'options-general.php',
        __('YACFP', 'yacfp'),
        __('YACFP', 'yacfp'),
        'manage_options',
        'yacfp',
        'yacfp_admin_page'
    );
}
add_action('admin_menu', 'yacfp_admin_menu');
// Enqueue admin scripts
function yacfp_admin_enqueue($hook) {
    // Enqueue block editor assets only in block editor
    if ($hook === 'post.php' || $hook === 'post-new.php' || $hook === 'site-editor.php' || $hook === 'widgets.php') {
        wp_enqueue_script(
            'yacfp-block',
            YACFP_PLUGIN_URL . 'blocks/yacfp-block.js',
            ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-block-editor', 'wp-components'],
            filemtime(YACFP_PLUGIN_DIR . 'blocks/yacfp-block.js') ?: '2025.08.28.01',
            true
        );
        wp_set_script_translations('yacfp-block', 'yacfp');
    }
}
add_action('admin_enqueue_scripts', 'yacfp_admin_enqueue');
// Register Gutenberg block
function yacfp_register_block() {
    register_block_type('yacfp/contact-form', [
        'api_version' => 2,
        'editor_script' => 'yacfp-block',
        'render_callback' => 'yacfp_shortcode',
    ]);
}
add_action('init', 'yacfp_register_block');
// Admin page callback
function yacfp_admin_page() {
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'submissions';
    echo '<div class="wrap">';
    echo '<h1>' . __('YACFP', 'yacfp') . '</h1>';
    if ($tab === 'submissions') {
        echo '<p><a href="' . admin_url('options-general.php?page=yacfp&tab=settings') . '" class="button button-primary">' . __('Settings', 'yacfp') . '</a></p>';
        yacfp_submissions_tab();
    } else {
        yacfp_settings_tab();
    }
    echo '</div>';
}
// Settings tab
function yacfp_settings_tab() {
    $settings = get_option('yacfp_settings', []);
    if (isset($_POST['yacfp_save_settings'])) {
        check_admin_referer('yacfp_settings_nonce');
        $settings['captcha_type'] = sanitize_text_field($_POST['captcha_type']);
        $settings['captcha_sitekey'] = sanitize_text_field($_POST['captcha_sitekey'] ?? '');
        $settings['captcha_secret'] = sanitize_text_field($_POST['captcha_secret'] ?? '');
        $settings['theme'] = sanitize_text_field($_POST['theme']);
        $settings['recipient'] = sanitize_email($_POST['recipient']);
        update_option('yacfp_settings', $settings);
        echo '<div class="updated"><p>' . __('Settings updated.', 'yacfp') . '</p></div>';
    }
    // Get themes
    $themes_dir = YACFP_PLUGIN_DIR . 'themes/';
    $themes = array_filter(scandir($themes_dir), function($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'css';
    });
    ?>
    <h2><?php _e('Settings', 'yacfp'); ?></h2>
    <form method="post">
        <?php wp_nonce_field('yacfp_settings_nonce'); ?>
        <p>
            <label><?php _e('CAPTCHA Type:', 'yacfp'); ?></label>
            <select name="captcha_type">
                <option value="disabled" <?php selected($settings['captcha_type'], 'disabled'); ?>><?php _e('Disabled', 'yacfp'); ?></option>
                <option value="turnstile" <?php selected($settings['captcha_type'], 'turnstile'); ?>><?php _e('Cloudflare Turnstile', 'yacfp'); ?></option>
                <option value="recaptcha" <?php selected($settings['captcha_type'], 'recaptcha'); ?>><?php _e('Google ReCaptcha V2', 'yacfp'); ?></option>
            </select>
        </p>
        <p>
            <label><?php _e('Site Key:', 'yacfp'); ?></label>
            <input type="text" name="captcha_sitekey" value="<?php echo esc_attr($settings['captcha_sitekey'] ?? ''); ?>">
        </p>
        <p>
            <label><?php _e('Secret Key:', 'yacfp'); ?></label>
            <input type="text" name="captcha_secret" value="<?php echo esc_attr($settings['captcha_secret'] ?? ''); ?>">
        </p>
        <p>
            <label><?php _e('Theme:', 'yacfp'); ?></label>
            <select name="theme">
                <?php foreach ($themes as $theme): ?>
                    <option value="<?php echo esc_attr($theme); ?>" <?php selected($settings['theme'], $theme); ?>><?php echo esc_html($theme); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label><?php _e('Recipient Email:', 'yacfp'); ?></label>
            <input type="email" name="recipient" value="<?php echo esc_attr($settings['recipient']); ?>">
        </p>
        <p><input type="submit" name="yacfp_save_settings" value="<?php _e('Save Settings', 'yacfp'); ?>" class="button-primary"></p>
    </form>
    <?php
}
// Submissions tab
function yacfp_submissions_tab() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'yacfp_submissions';
    $submissions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY submitted_at DESC");
    echo '<h2>' . __('Submissions', 'yacfp') . '</h2>';
    if ($submissions) {
        echo '<table class="wp-list-table widefat striped">';
        echo '<thead><tr><th>' . __('ID', 'yacfp') . '</th><th>' . __('Date', 'yacfp') . '</th><th>' . __('Referrer', 'yacfp') . '</th><th>' . __('IP Address', 'yacfp') . '</th><th>' . __('Data', 'yacfp') . '</th></tr></thead>';
        echo '<tbody>';
        foreach ($submissions as $sub) {
            echo '<tr>';
            echo '<td>' . $sub->id . '</td>';
            echo '<td>' . $sub->submitted_at . '</td>';
            echo '<td>' . esc_html($sub->referrer) . '</td>';
            echo '<td>' . esc_html($sub->user_ip) . '</td>';
            echo '<td><pre>' . esc_html(json_encode(json_decode($sub->form_data), JSON_PRETTY_PRINT)) . '</pre></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>' . __('No submissions yet.', 'yacfp') . '</p>';
    }
}
// Shortcode for form
function yacfp_shortcode() {
    $settings = get_option('yacfp_settings', []);
    $fields = [
        ['type' => 'text', 'label' => 'Name', 'required' => true],
        ['type' => 'email', 'label' => 'Email', 'required' => true],
        ['type' => 'text', 'label' => 'Subject', 'required' => true],
        ['type' => 'textarea', 'label' => 'Message', 'required' => true],
    ];
    ob_start();
    // Enqueue theme
    wp_enqueue_style('yacfp-theme', YACFP_PLUGIN_URL . 'themes/' . $settings['theme']);
    // Enqueue CAPTCHA scripts only for non-logged-in users
    if (!is_user_logged_in()) {
        if ($settings['captcha_type'] === 'recaptcha') {
            wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', [], null, true);
        } elseif ($settings['captcha_type'] === 'turnstile') {
            wp_enqueue_script('turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true);
        }
    }
    // Check for messages
    $message = '';
    if ($error = get_transient('yacfp_captcha_error_' . session_id())) {
        $message = '<div class="yacfp-error" style="color: red; margin-bottom: 10px;">' . esc_html($error) . '</div>';
        delete_transient('yacfp_captcha_error_' . session_id());
    } elseif ($success = get_transient('yacfp_success_' . session_id())) {
        $message = '<div class="yacfp-success" style="color: green; margin-bottom: 10px;">' . esc_html($success) . '</div>';
        delete_transient('yacfp_success_' . session_id());
    }
    ?>
    <?php echo $message; ?>
    <form id="yacfp-form" class="yacfp-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="yacfp_submit">
        <input type="hidden" name="yacfp_referer" value="<?php echo esc_url(get_permalink() ?: home_url()); ?>">
        <?php wp_nonce_field('yacfp_submit'); ?>
        <?php foreach ($fields as $field):
            $name = sanitize_title($field['label']);
            $required = $field['required'] ? 'required' : '';
        ?>
            <p>
                <label for="<?php echo $name; ?>"><?php echo esc_html($field['label']); ?></label>
                <?php if ($field['type'] === 'textarea'): ?>
                    <textarea id="<?php echo $name; ?>" name="<?php echo $name; ?>" <?php echo $required; ?>></textarea>
                <?php else: ?>
                    <input type="<?php echo $field['type']; ?>" id="<?php echo $name; ?>" name="<?php echo $name; ?>" <?php echo $required; ?>>
                <?php endif; ?>
            </p>
        <?php endforeach; ?>
        <?php if (!is_user_logged_in() && $settings['captcha_type'] !== 'disabled'): ?>
            <?php if ($settings['captcha_type'] === 'recaptcha'): ?>
                <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($settings['captcha_sitekey']); ?>"></div>
            <?php elseif ($settings['captcha_type'] === 'turnstile'): ?>
                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($settings['captcha_sitekey']); ?>"></div>
            <?php endif; ?>
        <?php endif; ?>
        <p><input type="submit" value="<?php _e('Submit', 'yacfp'); ?>"></p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('yacfp', 'yacfp_shortcode');
// Handle form submission
function yacfp_handle_submit() {
    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'yacfp_submit')) {
        $settings = get_option('yacfp_settings', []);
        $fields = [
            ['type' => 'text', 'label' => 'Name', 'required' => true],
            ['type' => 'email', 'label' => 'Email', 'required' => true],
            ['type' => 'text', 'label' => 'Subject', 'required' => true],
            ['type' => 'textarea', 'label' => 'Message', 'required' => true],
        ];
        $referer = isset($_POST['yacfp_referer']) ? esc_url_raw($_POST['yacfp_referer']) : home_url();
        // Get user IP (Cloudflare or REMOTE_ADDR)
        $user_ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']) : sanitize_text_field($_SERVER['REMOTE_ADDR']);
        // Validate CAPTCHA for non-logged-in users
        $captcha_valid = true;
        if (!is_user_logged_in() && $settings['captcha_type'] !== 'disabled') {
            if ($settings['captcha_type'] === 'recaptcha') {
                $response = $_POST['g-recaptcha-response'] ?? '';
                $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                    'body' => [
                        'secret' => $settings['captcha_secret'],
                        'response' => $response,
                    ]
                ]);
                $body = json_decode(wp_remote_retrieve_body($verify));
                $captcha_valid = $body->success;
            } elseif ($settings['captcha_type'] === 'turnstile') {
                $response = $_POST['cf-turnstile-response'] ?? '';
                $verify = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'body' => [
                        'secret' => $settings['captcha_secret'],
                        'response' => $response,
                    ]
                ]);
                $body = json_decode(wp_remote_retrieve_body($verify));
                $captcha_valid = $body->success;
            }
        }
        if (!$captcha_valid) {
            set_transient('yacfp_captcha_error_' . session_id(), __('CAPTCHA verification failed.', 'yacfp'), 60);
            if (WP_DEBUG) {
                error_log('YACFP: CAPTCHA validation failed, redirecting to ' . $referer);
            }
            wp_safe_redirect($referer);
            exit;
        }
        // Collect data
        $form_data = [];
        foreach ($fields as $field) {
            $name = sanitize_title($field['label']);
            if (isset($_POST[$name])) {
                $form_data[$field['label']] = sanitize_text_field($_POST[$name]);
            }
        }
        // Save to DB with referrer and user IP
        global $wpdb;
        $table_name = $wpdb->prefix . 'yacfp_submissions';
        $wpdb->insert($table_name, [
            'form_data' => json_encode($form_data),
            'referrer' => $referer,
            'user_ip' => $user_ip
        ]);
        // Prepare HTML email
        $subject = __('New Contact Form Submission', 'yacfp');
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;">
                <div style="background-color: #0073aa; color: #fff; padding: 15px; text-align: center;">
                    <h1 style="margin: 0; font-size: 24px;">New Contact Form Submission</h1>
                </div>
                <div style="padding: 20px; background-color: #fff; border: 1px solid #ddd;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <?php foreach ($form_data as $label => $value): ?>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; width: 30%;"><?php echo esc_html($label); ?>:</td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; width: 30%;">Submitted from:</td>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($referer); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; width: 30%;">User IP:</td>
                            <td style="padding: 8px; border: 1px solid #ddd;"><?php echo esc_html($user_ip); ?></td>
                        </tr>
                    </table>
                </div>
                <div style="text-align: center; padding: 10px; color: #777; font-size: 12px;">
                    <p style="margin: 0;">Sent by Yet Another Contact Form Plugin by Michael Staake</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        $message = ob_get_clean();
        // Send email
        wp_mail($settings['recipient'], $subject, $message, $headers);
        // Set success message
        set_transient('yacfp_success_' . session_id(), __('Form submitted successfully.', 'yacfp'), 60);
        if (WP_DEBUG) {
            error_log('YACFP: Form submitted, redirecting to ' . $referer);
        }
        wp_safe_redirect($referer);
        exit;
    }
}
add_action('admin_post_yacfp_submit', 'yacfp_handle_submit');
add_action('admin_post_nopriv_yacfp_submit', 'yacfp_handle_submit');
?>