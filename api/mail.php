<?php

/**
 * Sends transactional email through the DigiChefs mail API.
 * Configure MAIL_API_URL on the server to override the default endpoint.
 */
function academySendMail($to, $subject, $body)
{
    $endpoint = getenv('MAIL_API_URL') ?: 'https://digichefs.in/sajan/send-mail.php';

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('Academy mail not sent: invalid recipient address.');
        return false;
    }

    $payload = json_encode([
        'to' => $to,
        'subject' => $subject,
        'body' => $body,
    ]);

    if ($payload === false) {
        error_log('Academy mail not sent: unable to encode payload.');
        return false;
    }

    if (function_exists('curl_init')) {
        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $response = curl_exec($curl);
        $statusCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $statusCode < 200 || $statusCode >= 300) {
            error_log('Academy mail not sent: HTTP ' . $statusCode . ($error ? ' (' . $error . ')' : ''));
            return false;
        }

        return true;
    }

    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $payload,
        'timeout' => 15,
        'ignore_errors' => true,
    ]]);
    $response = @file_get_contents($endpoint, false, $context);
    $statusLine = $http_response_header[0] ?? '';

    if ($response === false || !preg_match('/\\s2\\d\\d\\s/', $statusLine)) {
        error_log('Academy mail not sent: HTTP request failed.');
        return false;
    }

    return true;
}

function academyMailEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function academyLoginEmail($name, $email, $password)
{
    $safeName = academyMailEscape($name);
    $safeEmail = academyMailEscape($email);
    $safePassword = academyMailEscape($password);

    return '<p>Hello ' . $safeName . ',</p>'
        . '<p>Your DigiChefs Academy account has been created. You can use the details below to sign in:</p>'
        . '<p><strong>Login URL:</strong> <a href="https://classroom.digichefs.com">classroom.digichefs.com</a><br>'
        . '<strong>Email:</strong> ' . $safeEmail . '<br>'
        . '<strong>Password:</strong> ' . $safePassword . '</p>'
        . '<p>For your security, please keep these details private.</p>'
        . '<p>Regards,<br>DigiChefs Academy</p>';
}

function academyClassAssignmentEmail($name, $classTitle)
{
    $safeName = academyMailEscape($name);
    $safeClassTitle = academyMailEscape($classTitle);

    return '<p>Hello ' . $safeName . ',</p>'
        . '<p>You have been assigned to <strong>' . $safeClassTitle . '</strong> on DigiChefs Academy.</p>'
        . '<p><a href="https://classroom.digichefs.com">Open DigiChefs Academy</a> to start learning.</p>'
        . '<p>Regards,<br>DigiChefs Academy</p>';
}
