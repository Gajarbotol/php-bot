<?php

$API_KEY = '7469065421:AAGu9xXd_ASPVOQSTCrwqzrIWnlUGUkWxf0';
$website = "https://api.telegram.org/bot".$API_KEY;
$userAgent = "TG-BOTS-GAJARBOTOLX";

function sendMessage($chat_id, $message) {
    global $website, $userAgent;
    $url = $website."/sendMessage?chat_id=".$chat_id."&text=".urlencode($message);

    $options = [
        'http' => [
            'header' => "User-Agent: $userAgent\r\n"
        ]
    ];
    $context = stream_context_create($options);
    file_get_contents($url, false, $context);
}

$update = json_decode(file_get_contents('php://input'), true);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'] ?? null;
    $text = $update['message']['text'] ?? null;
} else {
    $chat_id = null;
    $text = null;
}

$state_file = 'state_'.$chat_id.'.json';

if ($chat_id !== null && $text !== null) {
    if (!file_exists($state_file)) {
        file_put_contents($state_file, json_encode(['state' => 'start']));
    }

    $state = json_decode(file_get_contents($state_file), true);

    switch ($state['state']) {
        case 'start':
            sendMessage($chat_id, "Please provide your phone number:");
            $state['state'] = 'phone';
            break;

        case 'phone':
            if (substr($text, 0, 2) !== '01') {
                sendMessage($chat_id, 'The phone number must start with "01". Please provide a valid phone number:');
            } else {
                $state['phone'] = $text;
                sendMessage($chat_id, "Please provide your message:");
                $state['state'] = 'message';
            }
            break;

        case 'message':
            $phone = $state['phone'];
            $msg = $text;
            $url = "https://public-sms-api.onrender.com/send_sms?receiver=$phone&text=$msg";

            $options = [
                'http' => [
                    'header' => "User-Agent: $userAgent\r\n"
                ]
            ];
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            
            if ($response) {
                sendMessage($chat_id, 'Message sent successfully!');
            } else {
                sendMessage($chat_id, 'Failed to send message.');
            }

            $state['state'] = 'start';
            break;
    }

    file_put_contents($state_file, json_encode($state));
} else {
    error_log('No valid message received.');
}

?>
