<?php
$dir = sys_get_temp_dir();
$files = glob($dir . '/rate_*.json');
$count = 0;
foreach ($files as $file) {
    if (unlink($file)) {
        $count++;
    }
}
echo "Cleared $count rate limit files from $dir\n";
