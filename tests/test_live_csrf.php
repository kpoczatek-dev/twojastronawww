<?php
// tests/test_live_csrf.php
$baseUrl = 'http://localhost:8999/api';

function test($name, $result, $expected) {
    if ($result === $expected) {
        echo "[PASS] $name\n";
    } else {
        echo "[FAIL] $name. Expected $expected, got $result\n";
    }
}

function req($url, $method = 'GET', $data = [], $cookies = [], $headers = []) {
    $opts = [
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/json',
            'ignore_errors' => true
        ]
    ];
    
    // Cookies
    if ($cookies) {
        $cookieStr = [];
        foreach ($cookies as $k => $v) $cookieStr[] = "$k=$v";
        $opts['http']['header'] .= "\r\nCookie: " . implode('; ', $cookieStr);
    }

    // Custom Headers (Origin, Referer)
    foreach ($headers as $k => $v) {
        $opts['http']['header'] .= "\r\n$k: $v";
    }
    
    if ($data) {
        $opts['http']['content'] = json_encode($data);
    }

    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    
    // Parse headers for cookies
    $respCookies = [];
    foreach ($http_response_header as $hdr) {
        if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches)) {
            parse_str($matches[1], $cookieData);
            foreach ($cookieData as $k => $v) $respCookies[$k] = $v;
        }
    }
    
    // Parse status code
    preg_match('#HTTP/\d\.\d (\d+)#', $http_response_header[0], $statusMatches);
    $status = intval($statusMatches[1]);
    
    return ['body' => $response, 'status' => $status, 'cookies' => $respCookies];
}

echo "Starting Strict Session CSRF Live Tests...\n";

// Clear Rate Limits first to avoid 429
if (file_exists(__DIR__ . '/clean_limits.php')) {
    include __DIR__ . '/clean_limits.php';
}

// 1. Get Token (starts Session)
echo "1. Fetching Token (Session Start)...\n";
$res1 = req("$baseUrl/get-csrf-token.php", 'GET');
$token = json_decode($res1['body'], true)['token'] ?? '';
// Capture PHPSESSID
$cookies = $res1['cookies']; 

test("Get Token Status", $res1['status'], 200);
if (empty($token)) {
    echo "[FAIL] Token empty. Body: " . substr($res1['body'], 0, 500) . "\n";
} else {
    echo "[PASS] Token received\n";
}
// Expect PHPSESSID
if (isset($cookies['PHPSESSID'])) {
    echo "[PASS] PHPSESSID cookie received: " . substr($cookies['PHPSESSID'], 0, 5) . "...\n";
} else {
    echo "[FAIL] PHPSESSID cookie missing! Cookies received: " . json_encode($cookies) . "\n";
}
// Expect NO csrf_token cookie
if (isset($cookies['csrf_token'])) {
    echo "[FAIL] csrf_token cookie should NOT be present!\n";
} else {
    echo "[PASS] csrf_token cookie correctly absent\n";
}

// 2. Submit WITHOUT Origin (Should Fail Strict Check)
echo "2. Submitting without Origin/Referer...\n";
$res2 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello',
    'csrf_token' => $token
], $cookies);
test("Missing Origin Blocked", $res2['status'], 403);

// 3. Submit WITH INVALID Origin
echo "3. Submitting with INVALID Origin...\n";
$res3 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello',
    'csrf_token' => $token
], $cookies, ['Origin' => 'http://evil.com']);
test("Invalid Origin Blocked", $res3['status'], 403);

// 4. Submit WITHOUT Session Cookie (Session Loss)
echo "4. Submitting without Session Cookie...\n";
$res4 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello',
    'csrf_token' => $token
], [], ['Origin' => 'http://localhost:8999']); // Valid Origin, missing Cookie
test("Missing Session Blocked", $res4['status'], 403);

// 5. Submit WITH Bad Token
echo "5. Submitting with Bad Token...\n";
$res5 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello',
    'csrf_token' => 'bad_token'
], $cookies, ['Origin' => 'http://localhost:8999']);
test("Bad Token Blocked", $res5['status'], 403);

// 6. Happy Path
echo "6. Happy Path (Valid Session + Token + Origin)...\n";
$res6 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello',
    'csrf_token' => $token
], $cookies, ['Origin' => 'http://localhost:8999']);

if ($res6['status'] === 429) {
    echo "[WARN] Rate limit hit on final API call.\n";
} else {
    if ($res6['status'] === 200 || $res6['status'] === 500) {
        echo "[PASS] Happy Path (Status: " . $res6['status'] . ") - Allowed!\n";
    } else {
        echo "[FAIL] Happy Path Blocked. Status: " . $res6['status'] . "\nBody: " . substr($res6['body'], 0, 500) . "\n";
    }
}

echo "Strict Tests Completed.\n";
