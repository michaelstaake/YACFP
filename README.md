# YACFP - Yet Another Contact Form Plugin

**Author**: Michael Staake  
**License**: GPL-3.0  
**Compatibility**: Tested on WordPress 6.8.2 with PHP 8.3 on a cPanel server  

YACFP is a lightweight WordPress plugin that allows you to easily add a contact form anywhere on your site using a shortcode or block. Form submissions are emailed to you and can be viewed in the WordPress Admin panel.

## Features
- Insert contact forms using the `[yacfp]` shortcode or the YACFP Block.
- View and manage form submissions directly in the WordPress Admin panel.
- Configure form settings via the plugin's settings page.
- Lightweight and easy to use.

## Installation
1. Download the latest version of YACFP from [GitHub](https://github.com/michaelstaake/YACFP).
2. Upload the `YACFP` folder to your `wp-content/plugins` directory.
3. Navigate to **WordPress Admin > Plugins** and activate the YACFP plugin.

## Usage
1. After activation, locate **Contact Form - YACFP** in the WordPress Admin menu.
2. Visit the **Submissions** tab to view form submissions or the **Settings** tab to configure the plugin.
3. To display the form on your site:
   - Use the `[yacfp]` shortcode in any post, page, or widget area.
   - Alternatively, use the **YACFP Block** in the WordPress block editor.

## Email Configuration
- YACFP relies on the default WordPress mail function to send form submissions. However, due to modern hosting and email server configurations, emails may not be delivered reliably or may end up in spam.
- For reliable email delivery, it is strongly recommended to use a plugin like [FluentSMTP](https://wordpress.org/plugins/fluent-smtp/) (free, no affiliation). Configure an SMTP service to ensure submissions are delivered to your inbox.

## Customization
- To modify the form's appearance, avoid editing `default.css` in the plugin's `themes` folder to prevent changes from being overwritten during updates.
- Instead, create a new file (e.g., `custom.css`) in the `themes` folder.
- Go to **WordPress Admin > YACFP Settings** and select your custom CSS file to apply your styles.

## Compatibility
- Tested on WordPress 6.8.2 with PHP 8.3 on a cPanel server.
- Should work on any modern web server and recent or future WordPress versions.
- If you encounter issues, please report them on the [GitHub Issues page](https://github.com/michaelstaake/YACFP/issues).

## Support
- Technical support is not provided for this plugin. Thank you for your understanding.

## License
This plugin is licensed under the [GPL-3.0](https://www.gnu.org/licenses/gpl-3.0.en.html).