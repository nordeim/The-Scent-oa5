<?php
// File: clear_apcu_cache.php (Example script to run on the server)
// WARNING: Running this will clear ALL APCu user cache entries.
// This might affect other applications or parts of this application relying on APCu.
// Use with caution and ideally during a maintenance window or low-traffic period.

// Check if APCu is available
if (function_exists('apcu_clear_cache') && function_exists('apcu_enabled') && apcu_enabled()) {
    // Clear the user cache
    if (apcu_clear_cache()) {
        echo "APCu user cache cleared successfully.\n";
    } else {
        echo "Failed to clear APCu user cache (apcu_clear_cache returned false).\n";
    }

    // Optional: Clear system cache (usually requires specific ini settings, might not be needed/allowed)
    // if (function_exists('apcu_clear_cache') && apcu_clear_cache('system')) {
    //     echo "APCu system cache cleared successfully.\n";
    // } else {
    //     echo "Failed to clear APCu system cache or system cache not applicable.\n";
    // }

    // Optional: Check cache info after clearing
    if (function_exists('apcu_cache_info')) {
        echo "<pre>APCu Cache Info (After Clear):\n";
        print_r(apcu_cache_info());
        echo "</pre>";
    }

} else {
    echo "APCu extension is not available or not enabled on this server.\n";
}

?>
