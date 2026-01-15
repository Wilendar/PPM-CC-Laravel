<?php
// OPcache reset script - upload to public/ and access via browser
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache reset successfully at " . date('Y-m-d H:i:s');
} else {
    echo "OPcache not available";
}
// Self-delete for security
@unlink(__FILE__);
