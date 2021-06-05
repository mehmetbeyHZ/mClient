<?php

use MClient\Request;

require "../vendor/autoload.php";

// Get github user info

try {
    echo Request::get('https://www.instagram.com/')
        ->addCurlOptions(CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.129 Safari/537.36')
        ->execute()
        ->getResponse();

} catch (Exception $e) {
    echo $e->getMessage();
}
