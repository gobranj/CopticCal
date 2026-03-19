CopticCal
CopticCal is a WordPress plugin designed to calculate and display the liturgical fasts and feasts of the Coptic Orthodox Church. It features dynamic date calculations, event highlighting, and easy integration via shortcodes.
Description
This plugin provides a robust way to integrate a Coptic liturgical calendar into any WordPress site. It automatically calculates moveable feasts and fasts based on the Julian Easter date, accounts for both Gregorian and Coptic leap years, and includes a customizable settings menu for appearance.
Features
 * Moveable Date Calculations: Automatically calculates dates for Jonah's Fast, Great Lent, Pascha, and more based on the year.
 * Event Highlighting: Visually highlights the current day's event in the table with a customizable background color and a star (⭐) indicator.
 * Custom Settings Page: Provides an administrative menu to change the "Today" highlight color.
 * Shortcode Powered: Easily place the full calendar, upcoming events, or today's specific event anywhere on your site.
 * GitHub Update Checker: Built-in logic to check for new releases directly from GitHub for easy updates.
Installation
 * Download the CopticCal.php file.
 * Upload the file to your WordPress /wp-content/plugins/coptic-cal/ directory.
 * Activate the plugin through the 'Plugins' menu in WordPress.
Shortcodes
[cff_table]
Displays the full calendar of fasts and feasts for the year.
 * Attribute: year (optional). Defaults to the current Gregorian year.
 * Example: [cff_table year="2025"]
[cff_next]
Always displays the very next upcoming event (or the current one if it hasn't ended yet).
 * Example: [cff_next]
[cff_today]
Displays the name of the event only if it is occurring exactly today. If no event is active, it returns an empty string.
 * Example: [cff_today]
Configuration
Navigate to Settings > CopticCal in your WordPress dashboard to customize the Highlight Today Color. This color will be applied to the row in the [cff_table] that corresponds to the current date.
GitHub Updates
The plugin is configured to check for updates from the GitHub repository gobranj/CopticCal. To push an update to your users:
 * Update the version number in the plugin header and the $current_version variable in the code.
 * Create a new Release on GitHub with a tag (e.g., v1.1) that matches your new version number.
Author
 * Joseph Gobran
Version 1.0
