=== Total Pushover ===
Contributors: boogah
Donate link: http://paypal.me/boogah
Tags: pushover, notifications, email, wp_mail
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Redirects all outgoing site email to the Pushover notification service.

== Description ==

Total Pushover redirects all WordPress generated emails to the Pushover notification service. Pushover is an app that provides real-time notifications on your iOS or Android devices.

This plugin is especially handy when you don't want to worry about setting up either an SMTP or transactional mail plugin on a development sites.

It is suggested that this plugin only be used on single-user WordPress installs. Everything that your site would usually mail out — including password reset requests for all site users — will be sent to the Pushover account that you've configured.

= Features =
* Automatically intercepts all `wp_mail` function and redirects the output to Pushover.
* Logs errors if the notification fails to be sent to Pushover.
* Configuration via constants in `wp-config.php`.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory, or install the plugin directly via the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Add the following constants to your `wp-config.php` file:

   ```
   define('PUSHOVER_API_TOKEN', 'your-pushover-api-token');
   define('PUSHOVER_USER_KEY', 'your-pushover-user-key');
   ```

== Frequently Asked Questions ==

= Why is there not a settings page? =

This plugin should be considered a developer tool. If you are not comfortable adding constants to `wp-config.php` you should reconsider using this plugin on your site.

= How do I get a Pushover User Key and API Token? =

To use this plugin, you need both a Pushover User Key and an API Token:

1. **Create a Pushover Account**  
   First, visit [Pushover.net](https://pushover.net/) and create a free account. You can use the same account on both your desktop and mobile devices.

2. **Find Your User Key**  
   After logging in, navigate to your Pushover dashboard. Your **User Key** will be displayed under the "Your User Key" section. This is a unique identifier that allows you to receive notifications.

3. **Generate an API Token**  
   To generate a new API Token, visit [Pushover Apps](https://pushover.net/apps/build).  
   - Click "Create a New Application/API Token".  
   - Fill in the details like the application's name and description.  
   - After submitting, your **API Token** will be shown. This token authorizes the plugin to send notifications to your account.

4. **Configure in `wp-config.php`**  
   After copying both your User Key and API Token, add the following constants to your `wp-config.php` file:

   ```
   define('PUSHOVER_API_TOKEN', 'your-pushover-api-token');
   define('PUSHOVER_USER_KEY', 'your-pushover-user-key');
   ```

= How do I make sure I've configured my API Token and User Key correctly? =

As of version 1.1.0, there's a link on the "Plugins -> Installed Plugins" page (when the plugin is active) that will send you a test message.

= Would you consider adding Pushbullet support? =

This plugin was created and — continues to be maintained — for totally selfish personal reasons. Since I don't use Pushbullet at all, I don't feel that I could adequately support that service.

= Will you add support for per-user Pushover settings? =

I would prefer not to.

== Changelog ==

= 1.1.0 =
* Added Test link to Plugins page so you could check that things are configured correctly.

= 1.0.0 =
* Initial release of the plugin.

== License ==

This plugin is licensed under the GPLv2 or later. For more details, please refer to the license file in the plugin package or visit the [GPLv2 License page](https://www.gnu.org/licenses/gpl-2.0.html).
