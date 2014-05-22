<?php

// REST Web Service Server Demo, Playlist XML data returned 

require 'ytpl_php.php';
header('Content-type: text/xml');

try {

    if (isset($_GET['id'])) {
        $playlist = new YoutubePlayList($playlistID = $_GET['id'], $cacheAge = 1);
        echo $playlist->getXML();
    }
} catch (PlaylistNotFound $e) {
    header('HTTP/1.1 400 Playlist Not Found');
    echo $e->getMessage();
}
?>