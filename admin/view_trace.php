<?php
// Debug Trace Viewer
$logFile = __DIR__ . '/debug_trace.txt';
if (file_exists($logFile)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
} else {
    echo "No trace log found.";
}
?>
