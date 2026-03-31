<?php
require 'vendor/autoload.php';
$key = file_get_contents('storage/app/jaas_private_key.pk8');
$payload = ['iss' => 'chat', 'aud' => 'jitsi', 'sub' => 'vpaas-magic-cookie-5507144e28c44f7f808ad1071af6dece'];
$token = \Firebase\JWT\JWT::encode($payload, $key, 'RS256', 'vpaas-magic-cookie-5507144e28c44f7f808ad1071af6dece/1da589');
echo $token;
