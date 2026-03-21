# CopticCal

**CopticCal** is a lightweight, efficient WordPress plugin designed to calculate and display the liturgical fasts and feasts of the Coptic Orthodox Church. It features dynamic date calculations, visual event highlighting, and easy integration via shortcodes.

## 🚀 Features

* **Moveable Date Logic**: Automatically calculates dates for Jonah's Fast, Great Lent, Pascha, and the Apostles' Fast based on the Gregorian year.
* **Today Highlighting**: Automatically identifies the current day's event in the calendar table with a customizable background color and a star (⭐) indicator.
* **Coming Up Section**: A dedicated shortcode to show the nearest upcoming feast or fast.
* **GitHub Integration**: Built-in update checker that notifies you in WordPress when a new release is published to GitHub.
* **Admin Customization**: A dedicated settings page to choose your preferred highlight color using a native color picker.

## 🛠 Installation

1.  Download the `CopticCal.php` file.
2.  In your WordPress directory, navigate to `/wp-content/plugins/` and create a folder named `coptic-cal`.
3.  Upload `CopticCal.php` into that folder.
4.  Activate the plugin through the **Plugins** menu in your WordPress dashboard.

## 📖 Shortcodes

You can use the following shortcodes in any Post, Page, or Widget:

| Shortcode | Description |
| :--- | :--- |
| `[cff_table]` | Displays the full liturgical calendar for the year. Defaults to the current year. |
| `[cff_table year="2027"]` | Displays the calendar for a specific Gregorian year. |
| `[cff_next]` | Shows the very next upcoming event and its date range. |
| `[cff_today]` | Displays the name of the current event ONLY if one is happening today. |

## ⚙️ Configuration

To customize the look of your calendar:
1.  Go to **Settings > CopticCal** in your WordPress Admin dashboard.
2.  Use the **Highlight Today Color** picker to select a color that matches your site's theme.
3.  Click **Save Changes**.

## 👨‍💻 Developer & Updates

This plugin is maintained by **Joseph Gobran**. 
