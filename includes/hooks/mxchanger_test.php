<?php
/**
 * Simple test hook - upload to includes/hooks/mxchanger_test.php
 */

add_hook('AdminAreaHeadOutput', 1, function($vars) {
    return '<!-- MXCHANGER TEST HOOK LOADED -->';
});

add_hook('AdminAreaFooterOutput', 1, function($vars) {
    return '<script>console.log("MXCHANGER TEST: Hook is working!");</script>';
});
