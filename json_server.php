<?php

// REST Web Service Server Demo, Playlist JSON data returned 
error_reporting(0);
require 'ytpl_php.php';
header('Content-Type: application/json');
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);

if (isset($id)) {

    try {
        $playlist = new YoutubePlayList($playlistID = $id, $cacheAge = 1);
        echo $playlist->getJSON();
    } catch (PlaylistNotFound $e) {
        header('HTTP/1.1 404 Playlist Not Found');
        echo json_encode(array(
            "statusCode" => 404,
            "status" => "Playlist $id Not Found"
        ));
    }
}
