<?php

// REST Web Service Server Demo, Playlist XML data returned 
error_reporting(0);
require 'ytpl_php.php';
header('Content-type: text/xml');
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);

if (isset($id)) {

    try {
        $playlist = new YoutubePlayList($playlistID = $id, $cacheAge = 1);
        echo $playlist->getXML();
    } catch (PlaylistNotFound $e) {
        header('HTTP/1.1 404 Playlist Not Found');
        echo '<?xml version="1.0"?><playlist><status>Playlist ' . $id . ' Not Found</status>'
        . '<statusCode>404</statusCode></playlist>';
    }
}
