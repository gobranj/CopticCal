<?php
// Existing content above line 189... 

// Improved GitHub updater function
function improved_github_updater() {
    $plugin_data = get_file_data(__FILE__, ['Version' => 'Version']);
    $current_version = $plugin_data['Version'];
    $transient_key = 'copticcal_update';
    
    // Use transient caching for 12 hours
    if (false === ($updates = get_transient($transient_key))) {
        $response = wp_remote_get('https://api.github.com/repos/gobranj/CopticCal/releases/latest');
        
        // Validate JSON response
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            error_log('GitHub updater error: ' . $response->get_error_message());
            return;
        }
        
        $updates = json_decode(wp_remote_retrieve_body($response), true);
        
        // Complete update metadata
        if (isset($updates['tag_name']) && version_compare($current_version, $updates['tag_name'], '<')) {
            set_transient($transient_key, $updates, 12 * HOUR_IN_SECONDS);
        }
    }
    
    // Logic to perform update...
}

// Cache clearing function
function clear_copticcal_cache() {
    delete_transient('copticcal_update');
}

// Existing content below line 217... 
?