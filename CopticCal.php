<?php
/*
Plugin Name: CopticCal
Description: Coptic Calendar with event highlighting, "Next Event" display, and a custom settings menu.
Version: 1.0
Author: Joseph Gobran
*/

// --- 1. CORE CALCULATIONS ---

function cff_is_gregorian_leap($year) {
    return ($year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0));
}

function cff_is_coptic_leap($coptic_year) {
    return ($coptic_year % 4 === 3);
}

function cff_julian_easter($year) {
    $a = $year % 4;
    $b = $year % 7;
    $c = $year % 19;
    $d = (19 * $c + 15) % 30;
    $e = (2 * $a + 4 * $b - $d + 34) % 7;
    $month = floor(($d + $e + 114) / 31);
    $day = (($d + $e + 114) % 31) + 1;
    return strtotime("+13 days", mktime(0, 0, 0, $month, $day, $year));
}

function cff_format_date($timestamp) {
    return date("F j", $timestamp);
}

function cff_format_range($start, $end) {
    if (date("F", $start) === date("F", $end)) {
        return date("F j", $start) . "–" . date("j", $end);
    } else {
        return date("F j", $start) . " – " . date("F j", $end);
    }
}

function cff_calculate_events($year) {
    $coptic_year = $year + 284;
    $leap = cff_is_gregorian_leap($year);
    $cleap = cff_is_coptic_leap($coptic_year);
    $pascha = cff_julian_easter($year);
    $great_fast_start = strtotime("-55 days", $pascha);

    $events = [
        ["The Holy Nativity Fast (Cont.)", [mktime(0, 0, 0, 1, 1, $year), mktime(0, 0, 0, 1, 6, $year)]],
        ["The Holy Nativity Feast", $leap ? [mktime(0, 0, 0, 1, 7, $year), mktime(0, 0, 0, 1, 8, $year)] : mktime(0, 0, 0, 1, 7, $year)],
        ["The Circumcision Feast", mktime(0, 0, 0, 1, $leap ? 15 : 14, $year)],
        ["The Holy Epiphany", mktime(0, 0, 0, 1, $leap ? 20 : 19, $year)],
        ["Feast of the Wedding of Cana of Galilee", mktime(0, 0, 0, 1, $leap ? 22 : 21, $year)],
        ["Jonah's (Nineveh) Fast", [strtotime("-14 days", $great_fast_start), strtotime("-12 days", $great_fast_start)]],
        ["Jonah's (Nineveh) Feast", strtotime("-11 days", $great_fast_start)],
        ["Presentation of the Lord into the Temple", mktime(0, 0, 0, 2, $leap ? 16 : 15, $year)],
        ["Holy Great Fast", [$great_fast_start, strtotime("-9 days", $pascha)]],
        ["The Feast of the Cross", mktime(0, 0, 0, 3, 19, $year)],
        ["Annunciation Feast", mktime(0, 0, 0, 4, 7, $year)],
        ["Lazarus Saturday", strtotime("-8 days", $pascha)],
        ["Entry of our Lord into Jerusalem (Hosanna Sunday)", strtotime("-7 days", $pascha)],
        ["Holy Pascha", [strtotime("-6 days", $pascha), strtotime("-4 days", $pascha)]],
        ["Covenant Thursday", strtotime("-3 days", $pascha)],
        ["Good Friday", strtotime("-2 days", $pascha)],
        ["Glorious Feast of the Resurrection", $pascha],
        ["Feast of St. George", mktime(0, 0, 0, 5, 1, $year)],
        ["Thomas' Sunday", strtotime("+7 days", $pascha)],
        ["Martyrdom of St. Mark the Evangelist", mktime(0, 0, 0, 5, 8, $year)],
        ["The Holy Feast of Ascension", strtotime("+39 days", $pascha)],
        ["Entry of the Lord into Egypt", mktime(0, 0, 0, 6, 1, $year)],
        ["The Holy Pentecost Feast", strtotime("+49 days", $pascha)],
        ["The Apostles' Fast", [strtotime("+50 days", $pascha), mktime(0, 0, 0, 7, 11, $year)]],
        ["The Apostles' Feast (Martyrdom of St. Peter & St. Paul)", mktime(0, 0, 0, 7, 12, $year)],
        ["St. Mary's Fast", [mktime(0, 0, 0, 8, 7, $year), mktime(0, 0, 0, 8, 21, $year)]],
        ["Transfiguration Feast", mktime(0, 0, 0, 8, 19, $year)],
        ["Assumption of St. Mary's Body", mktime(0, 0, 0, 8, 22, $year)],
        ["The Nayrouz Feast (Coptic New Year)", mktime(0, 0, 0, 9, $cleap ? 12 : 11, $year)],
        ["The Feast of the Cross (Three days)", [mktime(0, 0, 0, 9, $cleap ? 28 : 27, $year), mktime(0, 0, 0, 9, ($cleap ? 28 : 27) + 2, $year)]],
        ["The Holy Nativity Fast", [mktime(0, 0, 0, 11, $cleap ? 26 : 25, $year), mktime(0, 0, 0, 1, 6, $year + 1)]],
    ];

    usort($events, function($a, $b) {
        $da = is_array($a[1]) ? $a[1][0] : $a[1];
        $db = is_array($b[1]) ? $b[1][0] : $b[1];
        return $da <=> $db;
    });
    return $events;
}

// --- 2. SETTINGS PAGE ---

add_action('admin_init', 'cff_settings_init');
function cff_settings_init() {
    register_setting('cff_settings', 'cff_highlight_color');
    add_option('cff_highlight_color', '#fff9c4'); 
}

add_action('admin_menu', 'cff_add_admin_menu');
function cff_add_admin_menu() {
    add_options_page('CopticCal Settings', 'CopticCal', 'manage_options', 'copticcal', 'cff_render_settings');
}

function cff_render_settings() {
    ?>
    <div class="wrap">
        <h1>CopticCal Settings</h1>
        <form action="options.php" method="post">
            <?php settings_fields('cff_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Highlight Today Color</th>
                    <td><input type="color" name="cff_highlight_color" value="<?php echo esc_attr(get_option('cff_highlight_color')); ?>"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// --- 3. RENDERING ---

function cff_render_table($atts) {
    $atts = shortcode_atts(['year' => date("Y")], $atts);
    $year = intval($atts['year']);
    $events = cff_calculate_events($year);
    $today = strtotime('today');
    $highlight = get_option('cff_highlight_color');

    $output = "<table style='width: 100%; border-collapse: collapse; text-align: left;'>";
    $output .= "<thead><tr style='border-bottom: 2px solid #ccc;'>
                <th style='padding:10px; text-align: left;'>Fast or Feast ($year)</th>
                <th style='padding:10px; text-align: left;'>Date</th>
                </tr></thead><tbody>";

    foreach ($events as [$name, $date]) {
        $start = is_array($date) ? $date[0] : $date;
        $end = is_array($date) ? $date[1] : $date;
        
        $is_today = ($today >= $start && $today <= $end);
        $style = $is_today ? "background-color: $highlight; font-weight: bold;" : "border-bottom: 1px solid #eee;";

        $output .= "<tr style='$style'>";
        $output .= "<td style='padding:8px; text-align: left;'>$name " . ($is_today ? "⭐" : "") . "</td>";
        $formatted_date = is_array($date) ? cff_format_range($date[0], $date[1]) : cff_format_date($date);
        $output .= "<td style='padding:8px; text-align: left;'>$formatted_date</td>";
        $output .= "</tr>";
    }
    $output .= "</tbody></table>";
    return $output;
}

// [cff_next] - Always shows the upcoming event
function cff_render_next_shortcode() {
    $events = cff_calculate_events(date('Y'));
    $today = strtotime('today');
    
    foreach ($events as [$name, $date]) {
        $end = is_array($date) ? $date[1] : $date;
        if ($end >= $today) {
            $formatted_date = is_array($date) ? cff_format_range($date[0], $date[1]) : cff_format_date($date);
            return "<div class='cff-next'><strong>Coming Up:</strong> $name ($formatted_date)</div>";
        }
    }
    return "";
}

// [cff_today] - Shows ONLY if something is happening right now
function cff_render_today_shortcode() {
    $events = cff_calculate_events(date('Y'));
    $today = strtotime('today');
    $active = [];

    foreach ($events as [$name, $date]) {
        $start = is_array($date) ? $date[0] : $date;
        $end = is_array($date) ? $date[1] : $date;
        if ($today >= $start && $today <= $end) {
            $active[] = $name;
        }
    }
    return !empty($active) ? "<span class='cff-today'>" . implode(", ", $active) . "</span>" : "";
}

add_shortcode('cff_table', 'cff_render_table');
add_shortcode('cff_next', 'cff_render_next_shortcode');
add_shortcode('cff_today', 'cff_render_today_shortcode');


// --- 5. GITHUB UPDATER ---

add_filter('site_transient_update_plugins', 'cff_push_update');
function cff_push_update($transient) {
    if (empty($transient->checked)) return $transient;

    // 1. Ensure the required WordPress function exists
    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    $username = 'gobranj';
    $repo = 'CopticCal';
    $plugin_file = __FILE__;
    $plugin_slug = plugin_basename($plugin_file);
    
    // 2. Automatically get version from the Plugin Header (top of this file)
    $plugin_data = get_plugin_data($plugin_file);
    $current_version = $plugin_data['Version'];
    
    $cache_key = 'cff_github_update_cache';
    $release = get_transient($cache_key);
    
    if ($release === false) {
        $url = "https://api.github.com/repos/$username/$repo/releases/latest";
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'CopticCal-Plugin'
            ]
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return $transient;
        }

        $release = json_decode(wp_remote_retrieve_body($response));
        
        if (json_last_error() === JSON_ERROR_NONE && !empty($release->tag_name)) {
            set_transient($cache_key, $release, 12 * HOUR_IN_SECONDS);
        } else {
            return $transient;
        }
    }

    $new_version = ltrim($release->tag_name, 'v');

    // 3. Compare versions
    if (version_compare($current_version, $new_version, '<')) {
        $update_obj = new stdClass();
        $update_obj->slug = 'copticcal';
        $update_obj->plugin = $plugin_slug;
        $update_obj->new_version = $new_version;
        $update_obj->url = "https://github.com/$username/$repo";
        $update_obj->package = $release->zipball_url;
        
        // Metadata for the "View Details" popup
        $update_obj->sections = [
            'description' => $plugin_data['Description'],
            'changelog'   => !empty($release->body) ? wp_kses_post($release->body) : 'Check GitHub for release notes.'
        ];

        $transient->response[$plugin_slug] = $update_obj;
    }

    return $transient;
}
