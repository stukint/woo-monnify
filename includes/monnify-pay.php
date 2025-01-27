<?php 
$checkout_url = $_GET['monnify_checkout'];

$options = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: MyPhpProxy/1.0\r\n"
    ]
];
$context = stream_context_create($options);
$html = file_get_contents($checkout_url, false, $context);

echo $html;