<?php

// REST Web Service Server Demo, Playlist JSON data returned 
error_reporting(0);
require 'ytpl_php.php';
header('Content-Type: application/json');

try {
    if (isset($_GET['id'])) {
        $playlist = new YoutubePlayList($playlistID = $_GET['id'], $cacheAge = 1);
        echo $playlist->getJSON();
    }
} catch (PlaylistNotFound $e) {
    header('HTTP/1.1 400 Playlist Not Found');
    echo json_encode(array(
        "status" => 400,
        "status_message" => "Playlist Not Found"
    ));
}
?>
