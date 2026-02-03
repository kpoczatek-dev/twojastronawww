<?php
function rateLimit($key, $limit, $seconds) {
    $dir = sys_get_temp_dir();
    $file = "$dir/rate_" . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . ".json";
    $now = time();

    if (!file_exists($file)) {
        file_put_contents($file, json_encode(["count" => 1, "time" => $now]));
        return true;
    }

    $data = json_decode(file_get_contents($file), true);
    if (!$data) {
        file_put_contents($file, json_encode(["count" => 1, "time" => $now]));
        return true;
    }

    if ($now - $data['time'] > $seconds) {
        file_put_contents($file, json_encode(["count" => 1, "time" => $now]));
        return true;
    }

    if ($data['count'] >= $limit) {
        return false;
    }

    $data['count']++;
    file_put_contents($file, json_encode($data));
    return true;
}
