<?php
/*
Plugin Name: CopticCal Pro
Description: Advanced Coptic Calendar with Coptic dates, event highlighting, and "Next Event" display.
Version: 1.2
Author: Joseph Gobran
*/

// --- 1. CORE CALCULATIONS & COPTIC CONVERSION ---

function cff_get_coptic_date($timestamp) {
    // Basic Gregorian to Coptic conversion (Approximate for 1901-2099)
    $g_day = (int)date('j', $timestamp);
    $g_month = (int)date('n', $timestamp);
    $g_year = (int)date('Y', $timestamp);

    $jd = GregorianToJD($g_month, $g_day, $g_year);
    // Coptic Epoch is JD 1825030
    $c_days = $jd - 1825030;
    
    $c_year = floor(($c_days) / 365.25);
    $remaining_days = $c_days - floor($c_year * 365.25);
    
    $c_month_num = floor($remaining_days / 30) + 1;
    $c_day = ($remaining_days % 30) + 1;

    $months = ["Tout", "Baba", "Hator", "Kiahk", "Toba", "Amshir", "Baramhat", "Baramouda", "Bashans", "Paona", "Epep", "Mesra", "Nasie"];
    
    // Safety check for the 13th month (Nasie)
    $month_name = isset($months[$c_month_num - 1]) ? $months[$c_month_num - 1] : "Nasie";
    
    return "$c_day $month_name";
}

// (Keep existing cff_is_gregorian_leap, cff_is_coptic_leap, cff_julian_easter, etc. from previous version)
function cff_is_gregorian_leap($year) { return ($year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0)); }
function cff_is_coptic_leap($cy) { return ($cy % 4 === 3); }
function cff_julian_easter($year) {
    $a = $year%4; $b = $year%7; $c = $year%19; $d = (19*$c+15)%30; $e = (2*$a+4*$b-$d+34)%7;
    $m = floor(($d+$e+114)/31); $day = (($d+$e+114)%31)+1;
    return strtotime("+13 days", mktime(0,0,0,$m,$day,$year));
}

function cff_calculate_events($year) {
    $coptic_year = $year + 284;
    $leap = cff_is_gregorian_leap($year);
    $cleap = cff_is_coptic_leap($coptic_year);
    $pascha = cff_julian_easter($year);
    $great_fast_start = strtotime("-55 days", $pascha);

    $events = [
        ["Nativity Fast (Cont.)", [mktime(0,0,0,1,1,$year), mktime(0,0,0,1,6,$year)]],
        ["Nativity Feast", $leap ? [mktime(0,0,0,1,7,$year), mktime(0,0,0,1,8,$year)] : mktime(0,0,0,1,7,$year)],
        ["Epiphany (Theophany)", mktime(0,0,0,1,$leap?20:19,$year)],
        ["Jonah's Fast", [strtotime("-14 days", $great_fast_start), strtotime("-12 days", $great_fast_start)]],
        ["Holy Great Fast", [$great_fast_start, strtotime("-9 days", $pascha)]],
        ["Hosanna Sunday", strtotime("-7 days", $pascha)],
        ["Holy Pascha Week", [strtotime("-6 days", $pascha), strtotime("-4 days", $pascha)]],
        ["Resurrection Feast", $pascha],
        ["Ascension Feast", strtotime("+39 days", $pascha)],
        ["Pentecost Feast", strtotime("+49 days", $pascha)],
        ["Apostles' Fast", [strtotime("+50 days", $pascha), mktime(0,0,0,7,11,$year)]],
        ["St. Mary's Fast", [mktime(0,0,0,8,7,$year), mktime(0,0,0,8,21,$year)]],
        ["Nayrouz (Coptic New Year)", mktime(0,0,0,9,$cleap?12:11,$year)],
        ["Nativity Fast", [mktime(0,0,0,11,$cleap?26:25,$year), mktime(0,0,0,1,6,$year+1)]],
    ];

    usort($events, function($a, $b) {
        $da = is_array($a[1]) ? $a[1][0] : $a[1];
        $db = is_array($b[1]) ? $b[1][0] : $b[1];
        return $da <=> $db;
    });
    return $events;
}

// --- 2. SETTINGS PAGE (Feature 7) ---

add_action('admin_init', 'cff_settings_init');
function cff_settings_init() {
    register_setting('cff_settings', 'cff_highlight_color');
    register_setting('cff_settings', 'cff_show_coptic');
    add_option('cff_highlight_color', '#fff9c4'); // Default light yellow
    add_option('cff_show_coptic', '1');
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
                <tr>
                    <th scope="row">Show Coptic Dates</th>
                    <td><input type="checkbox" name="cff_show_coptic" value="1" <?php checked(get_option('cff_show_coptic'), '1'); ?>></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// --- 3. RENDERING (Features 1 & 2) ---

function cff_render_table($atts) {
    $atts = shortcode_atts(['year' => date("Y")], $atts);
    $year = intval($atts['year']);
    $events = cff_calculate_events($year);
    $today = strtotime('today');
    $highlight = get_option('cff_highlight_color');
    $show_coptic = get_option('cff_show_coptic');

    $output = "<table style='width: 100%; border-collapse: collapse; text-align: left;'>";
    $output .= "<thead><tr style='border-bottom: 2px solid #ccc;'>
                <th style='padding:10px;'>Event</th>
                <th style='padding:10px;'>Gregorian Date</th>";
    if($show_coptic) $output .= "<th style='padding:10px;'>Coptic Date</th>";
    $output .= "</tr></thead><tbody>";

    foreach ($events as [$name, $date]) {
        $start = is_array($date) ? $date[0] : $date;
        $end = is_array($date) ? $date[1] : $date;
        
        // Feature 1: Highlight logic
        $is_today = ($today >= $start && $today <= $end);
        $style = $is_today ? "background-color: $highlight; font-weight: bold;" : "border-bottom: 1px solid #eee;";

        $output .= "<tr style='$style'>";
        $output .= "<td style='padding:8px;'>$name " . ($is_today ? "⭐" : "") . "</td>";
        $output .= "<td style='padding:8px;'>" . (is_array($date) ? date("M j", $start)."–".date("j", $end) : date("M j", $start)) . "</td>";
        
        // Feature 2: Coptic column
        if($show_coptic) {
            $output .= "<td style='padding:8px;'>" . cff_get_coptic_date($start) . "</td>";
        }
        $output .= "</tr>";
    }
    $output .= "</tbody></table>";
    return $output;
}

// --- 4. NEXT EVENT SHORTCODE (Feature 6) ---

function cff_render_next_event() {
    $events = cff_calculate_events(date('Y'));
    $today = strtotime('today');
    
    foreach ($events as [$name, $date]) {
        $start = is_array($date) ? $date[0] : $date;
        if ($start >= $today) {
            return "<div class='cff-next'><strong>Next:</strong> $name (" . date("M j", $start) . ")</div>";
        }
    }
    return "";
}

add_shortcode('cff_table', 'cff_render_table');
add_shortcode('cff_next', 'cff_render_next_event');
add_shortcode('cff_today', 'cff_current_event_shortcode'); // (Previous code)
