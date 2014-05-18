<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <title>PHP Yotube Playlist API Plugin Demo</title>
        <!-- please include the below ytpl_js.js and ytpl_style.css in every page 
        that will contain a playlist. To change the appearance of the playlist 
        modify the ytpl_style.css file.
        -->
        <link rel="stylesheet" type="text/css" href="ytpl_style.css" />
        <script type="text/javascript" src="ytpl_js.js"></script>
    </head>
    <body>
        <h3>PHP Youtube Playlist API Plugin Demo</h3>
        <?php
        error_reporting(0);
        // you must include ytpl_php.php in every page that will contain a playlist
        require 'ytpl_php.php';

        /* Example of some playlist ids 6B08BAA57B5C7810, nqdTIS_B64I7zbB_tPgvHiFTnmIqpT0u
         * @$playlistID
         * The ID of playlist you would like to load, this ID is appended to the playlist URL 
         * on youtube. Note: do not include the characters PL as part of the playlist ID
         * 
         * @$cacheAge
         * When the playlist is loaded for the first time it caches the data to improve loading 
         * performance.
         * $cacheAge is the max age of the cached data in hours before the program revisits youtube to update
         * the cache. So for example $cacheAge = 1 means the program will visit youtube again if the cached
         * data is more than an hour old.
         * setting $cacheAge to 0 will disable caching and always load the playlist data from youtube directly 
         * which will degrade loading performance.
         * If unset or left null, the default cacheAge is 7 days
         */
        try {
            $playlist = new YoutubePlayList($playlistID = "nqdTIS_B64I7zbB_tPgvHiFTnmIqpT0u", $cacheAge = 1);

            /* Call the display method in the place where you want the playlist to appear.
             * Set true or false to show and hide specific playlist data.
             */
            $playlist->display(array(
                'playlistTitle' => true,
                'playlistDescription' => true,
                'playlistVideoCount' => true,
                'videoTitle' => true,
                'videoDescription' => true,
                'videoImage' => true,
                'videoViews' => true,
                'videoAuthor' => false,
                'videoDuration' => true,
                'videoDatePublished' => false,
                'videoFavoritesCount' => false,
                'videoRaters' => true,
            ));
        } catch (PlaylistNotFound $e) {
            echo $e->getMessage();
        }
        ?>

    </body>
</html> 