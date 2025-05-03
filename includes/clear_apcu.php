<?php
// File: clear_apcu.php
// WARNING: Clears ALL APCu user cache entries. Remove after use.
header('Content-Type: text/plain');
if (function_exists('apcu_clear_cache') && function_exists('apcu_enabled') && apcu_enabled()) {
    if (apcu_clear_cache()) {      
        echo "APCu user cache cleared successfully.\n";
        if (function_exists('apcu_cache_info')) {
           echo "\nCurrent Cache Info:\n";
           print_r(apcu_cache_info());
        }
    } else {
        echo "ERROR: Failed to clear APCu user cache.\n";
    }
} else {
    echo "ERROR: APCu extension is not available or not enabled.\n";
}
?>

