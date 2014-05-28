<?php

// Demonstration on how to save and delete playlist data with mysql
error_reporting(0);
require 'ytpl_php.php';


try {

    $playlist = new YoutubePlayList($playlistID = "6B08BAA57B5C7810", $cacheAge = 1);
    $playlist->saveToMySQL($db_host = 'localhost', $uname = 'root', $password = '', $db_name = 'youtube_playlist'
            , $enableSchemaCreateStatement = true);


    $playlistTwo = new YoutubePlayList($playlistID = "nqdTIS_B64I7zbB_tPgvHiFTnmIqpT0u", $cacheAge = 1);
    $playlistTwo->saveToMySQL($db_host = 'localhost', $uname = 'root', $password = '', $db_name = 'youtube_playlist'
            , $enableSchemaCreateStatement = true);

//    Uncomment for a demo on how to delete the playlist data
//    $playlistTwo->deleteFromMySQL($db_host = 'localhost', $uname = 'root', $password = ''
//            , $db_name = 'youtube_playlist');
} catch (PlaylistNotFound $e) {
    echo $e->getMessage();
} catch (DatabaseException $e) {
    // You may what to send the error messages to an error log file instead of echoing them out.
    echo $e->getMessage();
}
?>
