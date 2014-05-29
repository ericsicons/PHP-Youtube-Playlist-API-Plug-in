<?php

/**
 * The YoutubePlayList class is used to create object instances representing a Youtube playlist with methods 
 * to display and return the playlist data in various formats.
 *
 * The constructor of the YoutubePlayList class takes a yotube Playlist ID and the Cache Age.
 * @example <br />
 * $playlist = new YoutubePlayList($playlistID = "nqdTIS_B64I7zbB_tPgvHiFTnmIqpT0u", $cacheAge = 1);<br />
 * $playlist->display(array $config)
 * @license   free
 * @version   3.0
 * @since     2014-Apr-23
 * @author    Eric Noguchi <eric@ericsicons.com>
 */
class YoutubePlayList {

    private $playList = array();
    private $videoList = array();

    /**
     * Creates a YoutubePlayList object from the specified playlistID.
     * @param string $playlistID The ID of playlist to load.
     * @param int $cacheAge The max age of the cached data in hours before the program revisits Youtube to update the 
     * cache.<br />Set to 0 to disable caching and always load the playlist data from Youtube.
     * @param int $startIndex For internal use. The Youtube video index number to start loading from.
     * @param boolean $notRecursiveCall For internal use, used to determine if the YoutubePlayList object was 
     * created internally.
     *  
     * @throws PlaylistNotFound
     */
    public function YoutubePlayList($playlistID, $cacheAge = 168, $startIndex = 1, $notRecursiveCall = true) {
        $this->playList['id'] = "PL" . $playlistID;

        $fileDir = "ytpl_cache/";
        $videoFile = $fileDir . $this->getID() . "_videos.txt";
        $playlistFile = $fileDir . $this->getID() . "_playlist.txt";

        if ($notRecursiveCall && file_exists($videoFile) && file_exists($playlistFile) && floor((time() -
                        strtotime(date("F d Y H:i:s", filemtime($videoFile)))) / 3600) < $cacheAge) {
            $this->videoList = unserialize($this->readFile(($videoFile)));
            $this->playList = unserialize($this->readFile(($playlistFile)));
        } else {
            $xml = simplexml_load_file('http://gdata.youtube.com/feeds/api/playlists/' . $playlistID
                    . '?max-results=50&start-index=' . $startIndex);
            if (!$xml) {
                throw new PlaylistNotFound(".: Error! Opening Playlist " . $this->getID()
                . ", Please check if the playlist ID is valid :.");
            }

            $t = $xml->children('openSearch', true);

            $this->playList['title'] = (string) $xml->title;
            $this->playList['description'] = (string) $xml->subtitle;
            $this->playList['numVideos'] = (string) $t->totalResults;

            $i = -1;
            foreach ($xml->entry as $entry) {
                $i++;
                // YT XML elements references
                $media = $entry->children('media', true);
                $yt = $entry->children('yt', true);
                $gd = $entry->children('gd', true);

                /* Creating the Video Objects */
                $this->videoList[] = new YouTubeVideo();
                $t = $media->group->player->attributes();
                parse_str(parse_url($t['url'], PHP_URL_QUERY), $vars);
                $this->videoList[$i]->setId($vars['v']);
                $t = $media->group->thumbnail[1]->attributes();
                $this->videoList[$i]->setThumbnail($t['url']);
                $this->videoList[$i]->setTitle($media->group->title);
                $this->videoList[$i]->setDescription($media->group->description);
                $t = $media->group->content->attributes();
                $this->videoList[$i]->setDuration($t['duration']);
                $this->videoList[$i]->setDatePublished($entry->published);
                $this->videoList[$i]->setAuthor($entry->author->name);
                $t = $yt->statistics->attributes();
                $this->videoList[$i]->setViews($t['viewCount']);
                $this->videoList[$i]->setFavorites($t['favoriteCount']);
                $t = $gd->rating->attributes();
                $this->videoList[$i]->setNumRaters($t['numRaters']);
            }


            if ($notRecursiveCall) {
                //  creating sub-playlists and merging them to main playlist as YT allows 50 videos max per request 
                $numReqs = ceil($this->playList['numVideos'] / 50);
                if ($numReqs > 1) {
                    $temp = array();
                    for ($x = 1; $x < $numReqs; $x++) {
                        $temp[$x] = new YoutubePlayList($playlistID, null, (50 * $x) + 1, false);
                        $this->videoList = array_merge($this->videoList, $temp[$x]->getVideoListArray());
                    }
                }
                // creating cache directory and files
                if ($cacheAge > 0) {
                    $dirname = dirname($videoFile);
                    if (!is_dir($dirname)) {
                        mkdir($dirname, 0755, true) or die("can not create youtube playlist cache directory");
                    }
                    $this->writeFile($videoFile, serialize($this->videoList));
                    $this->writeFile($playlistFile, serialize($this->playList));
                }
            }
        }
    }

    /**
     * Returns an array of all the YoutubeVideo objects.
     * @return Array
     */
    public function getVideoListArray() {
        return $this->videoList;
    }

    /**
     * Returns a YoutubeVideo object given an index.
     * @param int $id The index of Youtube video in the playlist.
     * @return YoutubeVideo
     */
    public function getVideoByIndex($i) {
        return $this->videoList[$i];
    }

    /**
     * Returns a YoutubeVideo object given a video id.
     * @param string $id The id of you Youtube video.
     * @return YoutubeVideo
     */
    public function getVideoById($id) {
        foreach ($this->videoList as $video) {
            if ($video->getId() == $id) {
                return $video;
            }
        }
    }

    /**
     * Returns the id of the playlist.
     * @return string
     */
    public function getID() {
        return $this->playList['id'];
    }

    /**
     * Returns the title of the playlist.
     * @return string
     */
    public function getTitle() {
        return $this->playList['title'];
    }

    /**
     * Returns the description of the playlist.
     * @return string
     */
    public function getDescription() {
        return $this->playList['description'];
    }

    /**
     * Returns the total number of videos 
     * in the playlist.
     * @return int
     */
    public function getNumOfVideos() {
        return intval($this->playList['numVideos']);
    }

    /**
     * Returns the Youtube URL of the playlist.
     * @return string
     */
    public function getURL() {
        return "https://www.youtube.com/playlist?list=" . $this->getID();
    }

    /**
     * Deletes all the playlist data from the database
     * @param string $db_host Database hostname, default is localhost
     * @param string $uname Username to access the database, default is root
     * @param string $password Password to access the database, default is ''
     * @param string $db_name Name of the database to delete the current playlist data from, default is youtube_playlist
     * @throws DatabaseException
     * @see mysql_operations_demo.php
     */
    public function deleteFromMySQL($db_host = 'localhost', $uname = 'root', $password = ''
    , $db_name = 'youtube_playlist') {
        $db = new PDODatabaseConnection($db_host, $uname, $password);
        // if the playlist is already in mysql delete all related data before reinserting new data
        $t = $db->query('SELECT * FROM `' . $db_name . '`.`playlist` WHERE `id` = :id;', null, array(
            ':id' => $this->getID()));
        if ($t->rowCount() > 0) {

            // running the below query to delete the playlist data from the
            // videos table since ON DELETE CASCADE will not work for databases not supporting ENGINE = InnoDB
            $db->query('DELETE FROM `' . $db_name . '`.`videos` WHERE `playlist_id` = :id;', null, array(
                ':id' => $this->getID()));
            $db->query('DELETE FROM `' . $db_name . '`.`playlist` WHERE `id` = :id;', null, array(
                ':id' => $this->getID()));
        }
        $db->close();
    }

    /**
     * Creates the playlist database and tables and saves the playlist data
     * @param string $db_host Database hostname, default is localhost
     * @param string $uname Username to access the database, default is root
     * @param string $password Password to access the database, default is ''
     * @param string $db_name Name of the database to create and save the playlist data into, default is youtube_playlist
     * @param boolean $enableSchemaCreateStatement If you don't have schema creation privileges set this to false and create
     * the database manually
     * @throws DatabaseException
     * @see mysql_operations_demo.php
     * 
     */
    public function saveToMySQL($db_host = 'localhost', $uname = 'root', $password = '', $db_name = 'youtube_playlist'
    , $enableSchemaCreateStatement = true) {

        $db = new PDODatabaseConnection($db_host, $uname, $password);
        if ($enableSchemaCreateStatement) {
            $db->query('CREATE SCHEMA IF NOT EXISTS `' . $db_name . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;');
        }
        $t = $db->query("SHOW DATABASES LIKE '$db_name'");
        if ($t->rowCount() == 0) {
            throw new DatabaseException(".: Error! Database $db_name does not exists :.");
        }
        $db->query('CREATE TABLE IF NOT EXISTS `' . $db_name . '`.`playlist` (
                    `id` VARCHAR(45) NOT NULL,
                    `title` TEXT NULL,
                    `description` TEXT NULL,
                    `numVideos` VARCHAR(3) NULL,
                    PRIMARY KEY (`id`))
                    ENGINE = InnoDB;'
        );
        $db->query('CREATE TABLE IF NOT EXISTS `' . $db_name . '`.`videos` (
                    `id` VARCHAR(20) NOT NULL,
                    `playlist_id` VARCHAR(45),
                    `title` TEXT NULL,
                    `description` TEXT NULL,
                    `duration` VARCHAR(6) NULL,
                    `thumbnail` VARCHAR(60) NULL,
                    `datePublished` DATE NULL,
                    `views` INT NULL,
                    `favorites` INT NULL,
                    `numRated` INT NULL,
                    `author` VARCHAR(30) NULL,
                    PRIMARY KEY (`id`),
                    INDEX `playlist_id_fk_idx` (`playlist_id` ASC),
                    CONSTRAINT `playlist_id_fk`
                      FOREIGN KEY (`playlist_id`)
                      REFERENCES `' . $db_name . '`.`playlist` (`id`)
                      ON DELETE CASCADE
                      ON UPDATE CASCADE)
                  ENGINE = InnoDB;'
        );

        // deleting old playlist data before inserting new data
        $this->deleteFromMySQL($db_host, $uname, $password, $db_name);

        $db->query('LOCK TABLES `' . $db_name . '`.`playlist` WRITE;');
        $db->query('INSERT INTO `' . $db_name . '`.`playlist` (
                    `id`,
                    `title`,
                    `description`,
                    `numVideos`)
                    VALUES
                    (:id,
                    :title,
                    :description,
                    :numVideos);
                    ', null, array(
            ':id' => $this->getID(),
            ':title' => $this->getTitle(),
            ':description' => $this->getDescription(),
            ':numVideos' => $this->getNumOfVideos()
        ));
        $db->query('UNLOCK TABLES;');


        $db->query('LOCK TABLES `' . $db_name . '`.`videos` WRITE;');
        foreach ($this->videoList as $v) {
            $db->query('INSERT INTO `' . $db_name . '`.`videos`
                        (`id`,
                        `playlist_id`,
                        `title`,
                        `description`,
                        `duration`,
                        `thumbnail`,
                        `datePublished`,
                        `views`,
                        `favorites`,
                        `numRated`,
                        `author`)
                        VALUES
                        (:id,
                        :playlist_id,
                        :title,
                        :description,
                        :duration,
                        :thumbnail,
                        :datePublished,
                        :views,
                        :favorites,
                        :numRated,
                        :author);
                        ', null, array(
                ':id' => $v->getID(),
                ':playlist_id' => $this->getID(),
                ':title' => $v->getTitle(),
                ':description' => $v->getDescription(),
                ':duration' => $v->getDuration(),
                ':thumbnail' => $v->getThumbnail(),
                ':datePublished' => $v->getDatePublished(),
                ':views' => $v->getViews(),
                ':favorites' => $v->getFavorites(),
                ':numRated' => $v->getNumRaters(),
                ':author' => $v->getAuthor()));
        }
        $db->query('UNLOCK TABLES;');
        $db->close();
    }

    /**
     * Returns all of the playlist data in JSON format. <br />
     * Key structure : {statusCode, status, id, title, description ,numVideos, videos:{ 
     * <br />videoIndex:{id, title, duration, thumbnail, datePublished, description, views, favorites, numRated, author
     * } } }
     * @return string
     * @see json_server.php
     */
    public function getJSON() {
        $JSONString = '{'
                . '"statusCode":"200",'
                . '"status":"OK",'
                . '"id":"' . $this->getID() . '",'
                . '"title":"' . $this->js($this->getTitle()) . '",'
                . '"description":"' . $this->js($this->getDescription()) . '",'
                . '"numVideos":"' . $this->getNumOfVideos() . '",'
                . '"videos":'
                . '{';
        foreach ($this->videoList as $k => $v) {
            $c = ($k === count($this->videoList) - 1) ? "" : ",";

            $JSONString.= '"' . $k . '":{'
                    . '"id":"' . $v->getID() . '",'
                    . '"title":"' . $this->js($v->getTitle()) . '",'
                    . '"duration":"' . $v->getDuration() . '",'
                    . '"thumbnail":"' . $v->getThumbnail() . '",'
                    . '"datePublished":"' . $v->getDatePublished() . '",'
                    . '"description":"' . $this->js($v->getDescription()) . '",'
                    . '"views":"' . $v->getViews() . '",'
                    . '"favorites":"' . $v->getFavorites() . '",'
                    . '"numRated":"' . $v->getNumRaters() . '",'
                    . '"author":"' . $v->getAuthor() . '"'
                    . '}'
                    . $c;
        }
        $JSONString.= "}}";
        //$this->writeFile($this->getID() . ".json", $JSONString);
        return $JSONString;
    }

    /**
     * Returns all of the playlist data in XML format.
     * @return string
     * @see xml_server.php
     */
    public function getXML() {


        $playlist = new SimpleXMLElement("<playlist></playlist>");
        $playlist->addChild("statusCode", "200");
        $playlist->addChild("status", "OK");
        $playlist->addChild("id", htmlspecialchars($this->getID(), ENT_QUOTES));
        $playlist->addChild("title", htmlspecialchars($this->getTitle(), ENT_QUOTES));
        $playlist->addChild("description", htmlspecialchars($this->getDescription(), ENT_QUOTES));
        $playlist->addChild("numVideos", htmlspecialchars($this->getNumOfVideos(), ENT_QUOTES));
        foreach ($this->videoList as $v) {
            $videos = $playlist->addChild("video");
            $videos->addChild('id', htmlspecialchars($v->getID(), ENT_QUOTES));
            $videos->addChild('title', htmlspecialchars($v->getTitle(), ENT_QUOTES));
            $videos->addChild('duration', htmlspecialchars($v->getDuration(), ENT_QUOTES));
            $videos->addChild('thumbnail', htmlspecialchars($v->getThumbnail(), ENT_QUOTES));
            $videos->addChild('datePublished', htmlspecialchars($v->getDatePublished(), ENT_QUOTES));
            $videos->addChild('description', $this->newLineToBR(htmlspecialchars($v->getDescription(), ENT_QUOTES)));
            $videos->addChild('views', htmlspecialchars($v->getViews(), ENT_QUOTES));
            $videos->addChild('favorites', htmlspecialchars($v->getFavorites(), ENT_QUOTES));
            $videos->addChild('numRated', htmlspecialchars($v->getNumRaters(), ENT_QUOTES));
            $videos->addChild('author', htmlspecialchars($v->getAuthor(), ENT_QUOTES));
        }

        return $playlist->asXML();
    }

    /**
     * Returns the playlist RSS feed. <br />
     * Set numOfVideos to the number of the videos to show in the RSS feed, default: the latest 10 videos.<br />
     * Set playlistUrl to your website URL where the playlist is located, default: Youtube's URL for the playlist.<br />
     * @param array $config array("showNumVideos" => int numOfVideos, "playListURL" => string playlistUrl) <br />
     * @return string
     * @see rss_demo.php
     */
    public function getRSS(array $config) {
        $config['showNumVideos'] = isset($config['showNumVideos']) ? intval($config['showNumVideos']) : 10;

        $RSSString = '<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0"><channel><title><![CDATA[' .
                $this->getTitle() .
                ']]></title><link>' . (isset($config['playListURL']) ? $config['playListURL'] : $this->getURL())
                . '</link><description><![CDATA[' . $this->getDescription() . ']]></description>';

        foreach ($this->videoList as $k => $v) {
            $RSSString.= '<item><title><![CDATA[' . $v->getTitle() . ']]></title>'
                    . '<description><![CDATA[<img src="' . $v->getThumbnail() . '" /><br />' . $v->getDescription() .
                    ']]></description>'
                    . '<link>' . (isset($config['playListURL']) ? $config['playListURL'] : $v->getUrl()) . '</link>'
                    . '<pubDate>' . $v->getDatePublished() . '</pubDate></item>';
            if ($k == ($config['showNumVideos'] - 1)) {
                break;
            }
        }
        $RSSString.= "</channel></rss>";

        return $RSSString;
    }

    /**
     * Builds and displays the playlist in HTML. <br />
     * Call this method in the location where you want the playlist to appear on your site.<br />
     * @see playlist_demo.php
     * @param array $show  Configuration array used to select which playlist data to show or hide. <br />
     */
    public function display(array $show) {
        print '<div id="yt_plContainer" class="yt_plContainer">';
        $show['playlistTitle'] and
                print '<div class="yt_title">&nbsp;' . $this->getTitle() .
                        '<input class="yt_button" type="button" value="Hide" onclick="showHide(\'description\',this)" /></div>';
        $show['playlistDescription'] and print '<div id="description" class="yt_description">' .
                        $this->newLineToBR($this->getDescription()) . '</div>';
        print '<div id="player"><iframe class="yt_player" src="//www.youtube.com/embed/' . $this->videoList[0]->getID()
                . '?rel=0"  allowfullscreen></iframe></div>';
        if ($show['videoDescription']) {
            print '<div class="yt_title">&nbsp;Video Information'
                    . '<input class="yt_button" type="button" value="Hide" onclick="showHide(\'videoInfo\',this)" /></div>';
            print '<div id="videoInfo" class="yt_description">' .
                    $this->newLineToBR($this->videoList[0]->getDescription()) . '</div>';
        }
        print '<div class="yt_title">&nbsp;Playlist';
        $show['playlistVideoCount'] and print ' (' . $this->getNumOfVideos() . ')';
        print '<input class="yt_button" type="button" value="Hide" onclick="showHide(\'youtubePl\',this)" />'
                . '<input class="yt_button" type="button" value="Play All"'
                . ' onclick="loadPlaylist(\'' . $this->getID() . '\')" /></div>';
        print '<div class="yt_tblContainer"><table id="youtubePl" class="youtubePl"><tr class="plHeader">';
        print '<th></th>';
        $show['videoImage'] and print '<th></th>';
        $show['videoTitle'] and print '<th>Title</th>';
        $show['videoAuthor'] and print '<th>Author</th>';
        $show['videoDatePublished'] and print '<th>Date Published</th>';
        $show['videoViews'] and print '<th>Views</th>';
        $show['videoFavoritesCount'] and print '<th>Favorites</th>';
        $show['videoRaters'] and print '<th>Rated</th>';
        $show['videoDuration'] and print '<th>Duration</th>';
        print '</tr>';

        foreach ($this->videoList as $i => $video) {
            print '<tr class="plTracks" onclick="loadVideo(\'' . $video->getID() . "'" . ($show['videoDescription'] ?
                            ",'" . htmlspecialchars($this->js($video->getDescription())) . "'" : "") . ')">';
            print '<th>' . ($i + 1) . '</th>';
            $show['videoImage'] and print '<td class="yt_img"><img alt="" class="yt_img" src=' . $video->getThumbnail()
                            . ' /></td>';
            $show['videoTitle'] and print '<td class="videoTitle">' . $video->getTitle() . '</td>';
            $show['videoAuthor'] and print '<td>' . $video->getAuthor() . '</td>';
            $show['videoDatePublished'] and print '<td>' . $video->getDatePublished() . '</td>';
            $show['videoViews'] and print '<td>' . $video->getViews() . '</td>';
            $show['videoFavoritesCount'] and print '<td>' . $video->getFavorites() . '</td>';
            $show['videoRaters'] and print '<td>' . $video->getNumRaters() . '</td>';
            $show['videoDuration'] and print '<td class="yt_duration">' . $video->getDuration() . '</td>';
            print '</tr>';
        }

        print "</table></div>";
        print "</div>";
    }

    private function writeFile($file, $str) {
        $f = fopen($file, "w");
        fwrite($f, $str);
        fclose($f);
    }

    private function readFile($file) {
        return file_get_contents($file);
    }

    private function js($str) {
        return $this->newLineToBR($this->encodeQuotes($str));
    }

    private function newLineToBR($str) {
        return preg_replace('/\r\n|\r|\n/', '<br />', $str);
    }

    private function encodeQuotes($str) {
        $removeSingle = str_replace("'", "&#39;", $str);
        return str_replace('"', "&#34;", $removeSingle);
    }

}

/**
 * The YouTubeVideo class is used to create object instances representing a Youtube Video with methods 
 * to set and get the video data, this class is for internal use.
 *
 * @license   free
 * @version   3.0
 * @since     2014-Apr-23
 * @author    Eric Noguchi <eric@ericsicons.com>
 */
class YouTubeVideo {

    private $id;
    private $title;
    private $duration;
    private $thumbnail;
    private $datePublished;
    private $description;
    private $views;
    private $favorites;
    private $numRated;
    private $author;

    /* set functions */

    public function setId($id) {
        $this->id = (string) $id;
    }

    public function setTitle($title) {
        $this->title = (string) $title;
    }

    public function setDuration($duration) {
        $this->duration = (string) $this->minutes($duration);
    }

    public function setThumbnail($thumbnail) {
        $this->thumbnail = (string) $thumbnail;
    }

    public function setDatePublished($datePublished) {
        $t = explode("T", $datePublished);
        $this->datePublished = (string) $t[0];
    }

    public function setViews($views) {
        $this->views = (string) ($views ? $views : "0");
    }

    public function setDescription($description) {
        $this->description = (string) $description;
    }

    public function setFavorites($favorites) {
        $this->favorites = (string) ($favorites ? $favorites : "0");
    }

    public function setNumRaters($numRated) {
        $this->numRated = (string) ($numRated ? $numRated : "0");
    }

    public function setAuthor($author) {
        $this->author = (string) $author;
    }

    /*   get functions        */

    // @return Returns the Id of the Video 
    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDuration() {
        return $this->duration;
    }

    public function getThumbnail() {
        return $this->thumbnail;
    }

    public function getUrl() {
        return "http://www.youtube.com/watch?v=" . $this->getId();
    }

    public function getDatePublished() {
        return $this->datePublished;
    }

    public function getViews() {
        return $this->views;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getFavorites() {
        return $this->favorites;
    }

    public function getNumRaters() {
        return $this->numRated;
    }

    public function getAuthor() {
        return $this->author;
    }

    /* utility functions */

    private function minutes($seconds) {
        $second = intVal($seconds);
        $mod = $seconds % 60;
        return floor($seconds / 60) . ":" . ($mod < 10 ? "0" . $mod : $mod);
    }

}

/**
 * Class to handle the exception thrown when the supplied playlist ID is invalid
 *
 * @license   free
 * @version   3.0
 * @since     2014-Apr-23
 * @author    Eric Noguchi <eric@ericsicons.com>
 */
class PlaylistNotFound extends Exception {

    public function PlaylistNotFound($message) {
        parent::__construct($message);
    }

}

/**
 * Class to handle the possible exceptions thrown when working with MySQL databse.
 *
 * @license   free
 * @version   3.0
 * @since     2014-May-27
 * @author    Eric Noguchi <eric@ericsicons.com>
 */
class DatabaseException extends Exception {

    public function DatabaseException($message) {
        parent::__construct($message);
    }

}

/**
 * PDO Wrapper Class used for creating connections and querying the database.
 *
 * @license   free
 * @version   3.0
 * @since     2014-Apr-23
 * @author    Eric Noguchi <eric@ericsicons.com>
 */
class PDODatabaseConnection {

    private $con = "";

    /** Creates a PDO database connection
     * @param string $db_host Database hostname
     * @param string $uname Username to access the database
     * @param string $password Password to access the database
     * @throws DatabaseException
     */
    public function PDODatabaseConnection($db_host, $uname, $password) {
        try {
            $this->con = new PDO('mysql:host=' . $db_host . ';', $uname, $password);
        } catch (PDOException $e) {
            throw new DatabaseException(".: Error! " . $e->getMessage() . " :.");
        }
    }

    /**
     * Executes MySQL queries
     * @param string $sql MySQL statement to be executed
     * @param string $columnName If set, the query method will return the data of first row for the specified column 
     * name. <br /> Should be used on a queries which will result in a single row count.
     * @param Array $bind Array used for PDO Param binding on the MySQL statment .
     * @throws DatabaseException
     * @return PDOStatement|String Returns a PDO prepared statement object if $columnName is not set otherwise it 
     * will return a string.
     * 
     */
    public function query($sql, $columnName = null, $binds = null) {
        $result = null;
        $dbh = $this->con;
        $stmt = $dbh->prepare($sql);
        if (!$stmt->execute($binds)) {
            throw new DatabaseException(".: Error! unable to execute query [ <strong>$sql</strong> ]<br/>:.");
        }
        if ($columnName != null) {
            if ($row = $stmt->fetch()) {
                $result = $row[$columnName];
            }
            return $result;
        } else {
            return $stmt;
        }
    }

    /**
     * Closes the PDO connection
     */
    function close() {
        $this->con = null;
    }

}
