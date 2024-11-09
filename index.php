<?php

require __DIR__.'/vendor/autoload.php';

use Endroid\QrCode\QrCode;

function smallHash($text)
{
    $t = rtrim(base64_encode(hash('crc32', $text, true)), '=');
    return strtr($t, '+/', '-_');
}

$content = '';

// Check if the request method is POST and if the URL is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    $url = $_POST['url'];

    // Ensure the URL starts with http:// or https://
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        $url = 'http://'.$url;
    }

    $urlhash = smallHash($url);
    $hashfolder = substr($urlhash, 0, 2);
    $hashfile = substr($urlhash, 2);

    $hashfolderpath = './db/'.$hashfolder;
    $hashfilepath = $hashfolderpath.'/'.$hashfile;

    // Create the directory if it doesn't exist
    if (!is_dir($hashfolderpath)) {
        mkdir($hashfolderpath, 0700, true);
    }

    // Save the URL to a file
    file_put_contents($hashfilepath, $url);

    // Generate the short URL without exposing index.php
    $shortUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $urlhash;

    // Generate the QR code for the short URL
    $qrcode = (new QrCode())->setText($shortUrl);

    // Prepare the content to display the short URL and QR code
    $content = '<a href="'.$shortUrl.'">'.$shortUrl.'</a><br>'
        .'<img src="data:'.$qrcode->getContentType().';base64,'.base64_encode($qrcode->get()).'">';
} elseif (!empty($_GET)) {
    // Handle the case where a hash is provided in the URL
    $urlhash = key($_GET);
    $hashfolder = substr($urlhash, 0, 2);
    $hashfile = substr($urlhash, 2);

    $hashfolderpath = './db/'.$hashfolder;
    $hashfilepath = $hashfolderpath.'/'.$hashfile;

    // Check if the file exists
    $findfiles = glob($hashfilepath);
    if (!empty($findfiles)) {
        $fullfilepath = current($findfiles);
        header('Location:'.file_get_contents($fullfilepath));
        exit;
    }

    $content = 'No link matches this identifier.';
} else {
    // Show the form for URL input
    $content = '<form method="post">
                Enter your URL: <input type="text" name="url"><input type="submit" value="Submit">
            </form>';
}

// Actual page output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shuri</title>
</head>
<body>
    <div id="content">
        <?= $content ?>
    </div>
</body>
</html>
