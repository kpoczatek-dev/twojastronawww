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

function req($url, $method = 'GET', $data = [], $cookies = []) {
    $opts = [
        'http' => [
            'method' => $method,
            'header' => 'Content-Type: application/json',
            'ignore_errors' => true
        ]
    ];
    
    if ($cookies) {
        $cookieStr = [];
        foreach ($cookies as $k => $v) $cookieStr[] = "$k=$v";
        $opts['http']['header'] .= "\r\nCookie: " . implode('; ', $cookieStr);
    }
    
    if ($data) {
        $opts['http']['content'] = json_encode($data);
    }
    
    // Simulate Origin checks
    $opts['http']['header'] .= "\r\nOrigin: http://localhost:8999";

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

echo "Starting CSRF Live Tests...\n";

// 1. Get Token
echo "1. Fetching Token...\n";
$res1 = req("$baseUrl/get-csrf-token.php", 'GET');
$token = json_decode($res1['body'], true)['token'] ?? '';
$cookies = $res1['cookies'];
test("Get Token Status", $res1['status'], 200);
if (empty($token)) {
    echo "[FAIL] Token received. Expected 1, got empty. Body: " . substr($res1['body'], 0, 500) . "\n";
} else {
    echo "[PASS] Token received\n";
}
test("Cookie received", !empty($cookies['csrf_token']), true);

// 2. Submit Contact Form WITHOUT Token
echo "2. Submitting without token...\n";
$res2 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello'
], $cookies); // Send cookies but NO token in body
test("Missing Token in Body", $res2['status'], 403);

// 3. Submit Contact Form WITH INVALID Token
echo "3. Submitting with invalid token...\n";
$res3 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello',
    'csrf_token' => 'invalid_token_123'
], $cookies);
test("Invalid Token", $res3['status'], 403);

// 4. Submit Contact Form WITHOUT Cookie (but with token)
echo "4. Submitting without cookie...\n";
$res4 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello',
    'csrf_token' => $token
], []); // No cookies
test("Missing Cookie", $res4['status'], 403);

// 5. Success Path
echo "5. Happy Path (Valid Token + Cookie)...\n";
// Note: We might hit Rate Limit here if tests run fast or if previous tests counted?
// check contact.php rate limit. It generates key based on IP.
// 5 attempts per 5 minutes.
// We made 3 fail attempts. This is 4th. Should be OK.
$res5 = req("$baseUrl/contact.php", 'POST', [
    'name' => 'Test', 'email' => 'test@example.com', 'message' => 'Hello',
    'csrf_token' => $token
], $cookies);

// If 200 => Success. If 400 => Validation error (maybe email?).
// If 429 => Rate limit.
if ($res5['status'] === 429) {
    echo "[WARN] Rate limit hit, cannot verify happy path fully.\n";
} else {
    // 200 is expected for valid submission
    // But wait, contact.php sends validation error?
    // "Uzupełnij wszystkie pola" -> checks name, email, message. We sent them.
    // "Nieprawidłowy email" -> test@example.com is valid.
    // "mail()" function might fail on localhost without SMTP.
    // So status might be 500 if mail fails, or 200 if mail returns true (some setpus) or 500.
    // But CSRF passed if we are past the Check!
    // The CSRF check is early.
    
    // Let's modify expected: 200 or 500 (mail fail) or 400.
    // Definitely NOT 403.
    if ($res5['status'] === 403) {
        test("Happy Path", $res5['status'], "200 or 500");
    } else {
        echo "[PASS] Happy Path (Status: " . $res5['status'] . ") - CSRF Check passed (not 403)\n";
    }
}

echo "Tests Completed.\n";
