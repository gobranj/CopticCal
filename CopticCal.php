<?php
/*
Plugin Name: CopticCal
Description: Displays Coptic fasts and feasts for a given Gregorian year in a simple table (horizontal lines only).
Version: 1.0
Author: Joseph Gobran
*/

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
    $covenant_thursday = strtotime("-3 days", $pascha);
    $good_friday = strtotime("-2 days", $pascha);
    $hosanna_sunday = strtotime("-7 days", $pascha);
    $lazarus_saturday = strtotime("-8 days", $pascha);
    $great_fast_start = strtotime("-55 days", $pascha);
    $lent_end = strtotime("-9 days", $pascha);
    $pascha_start = strtotime("-6 days", $pascha);
    $pascha_end = strtotime("-4 days", $pascha);
    $jonah_fast_start = strtotime("-14 days", $great_fast_start);
    $jonah_feast = strtotime("+3 days", $jonah_fast_start);
    
    $events = [
        // ADDED: Continuation of the Nativity fast from the previous year
        ["The Holy Nativity Fast", [mktime(0, 0, 0, 1, 1, $year), mktime(0, 0, 0, 1, 6, $year)]],
        ["The Holy Nativity Feast", $leap ? [mktime(0, 0, 0, 1, 7, $year), mktime(0, 0, 0, 1, 8, $year)] : mktime(0, 0, 0, 1, 7, $year)],
        ["The Circumcision Feast", mktime(0, 0, 0, 1, $leap ? 15 : 14, $year)],
        ["The Holy Epiphany", mktime(0, 0, 0, 1, $leap ? 20 : 19, $year)],
        ["Feast of the Wedding of Cana of Galilee", mktime(0, 0, 0, 1, $leap ? 22 : 21, $year)],
        ["Jonah's (Nineveh) Fast", [$jonah_fast_start, strtotime("+2 days", $jonah_fast_start)]],
        ["Jonah's (Nineveh) Feast", $jonah_feast],
        ["Presentation of the Lord into the Temple", mktime(0, 0, 0, 2, $leap ? 16 : 15, $year)],
        ["Holy Great Fast", [$great_fast_start, $lent_end]],
        ["The Feast of the Cross", mktime(0, 0, 0, 3, 19, $year)],
        ["Annunciation Feast", mktime(0, 0, 0, 4, 7, $year)],
        ["Lazarus Saturday", $lazarus_saturday],
        ["Entry of our Lord into Jerusalem (Hosanna Sunday)", $hosanna_sunday],
        ["Holy Pascha", [$pascha_start, $pascha_end]],
        ["Covenant Thursday", $covenant_thursday],
        ["Good Friday", $good_friday],
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
        $dateA = is_array($a[1]) ? $a[1][0] : $a[1];
        $dateB = is_array($b[1]) ? $b[1][0] : $b[1];
        // FIXED: Using spaceship operator for safer comparison
        return $dateA <=> $dateB; 
    });

    return $events;
}
function cff_render_table($atts) {
    // 1. Set the default year from the shortcode attribute or current year
    $atts = shortcode_atts(['year' => date("Y")], $atts);
    $year = intval($atts['year']);
    
    // 2. Check if the user has submitted a new year via the input box
    if (isset($_POST['cff_year']) && intval($_POST['cff_year']) > 0) {
        $year = intval($_POST['cff_year']);
    }

    // 3. Calculate the events for the determined year
    $events = cff_calculate_events($year);

    // 4. Build the output (Form + Table)
    $output = "<div class='cff-calendar-container'>";
    
    // The Year Input Form
    $output .= "<form method='POST' action='' class='cff-year-form' style='margin-bottom: 20px;'>";
    $output .= "<label for='cff_year_input'><strong>Select Year: </strong></label>";
    $output .= "<input type='number' id='cff_year_input' name='cff_year' value='" . esc_attr($year) . "' required min='1900' max='2200' style='width: 100px; padding: 5px; margin-right: 10px;'>";
    $output .= "<button type='submit' style='padding: 5px 15px;'>Generate Calendar</button>";
    $output .= "</form>";

    // The Calendar Table
    $output .= "<table class='cff-table' style='width: 100%; text-align: left; border-collapse: collapse;'>";
    // Appended the selected year to the header so the user knows what they are looking at
    $output .= "<thead><tr><th style='border-bottom: 2px solid #ccc; padding-bottom: 10px;'>Fast or Feast ($year)</th><th style='border-bottom: 2px solid #ccc; padding-bottom: 10px;'>Date</th></tr></thead>";
    $output .= "<tbody>";

    foreach ($events as [$name, $date]) {
        $formatted = is_array($date) ? cff_format_range($date[0], $date[1]) : cff_format_date($date);
        $output .= "<tr><td style='border-bottom: 1px solid #eee; padding: 8px 0;'>$name</td><td style='border-bottom: 1px solid #eee; padding: 8px 0;'>$formatted</td></tr>";
    }

    $output .= "</tbody></table>";
    $output .= "</div>";

    return $output;
}
function cff_current_event_shortcode() {
    $year = (int)date('Y');
    $today = mktime(0, 0, 0, date('n'), date('j'), $year);
    $events = cff_calculate_events($year);
    $matches = [];

    foreach ($events as $event) {
        $name = $event[0];
        $date_info = $event[1];

        if (is_array($date_info)) {
            if ($today >= $date_info[0] && $today <= $date_info[1]) {
                $matches[] = $name;
            }
        } else {
            if ($today == $date_info) {
                $matches[] = $name;
            }
        }
    }

    if (!empty($matches)) {
        return nl2br(esc_html(implode("\n", $matches)));
    }

    return '';
}


add_shortcode('cff_table', 'cff_render_table');
add_shortcode('cff_today', 'cff_current_event_shortcode');
