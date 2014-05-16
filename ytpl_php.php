<?php

/*
 * Youtube Playlist API by Eric Noguchi
 * Version 1.2 
 * Code is available at http://www.ericsicons.com/yt_playlistapi
 */
 
class YoutubePlayList {

    private $playList = array();
    private $videoList = array();

    public function __construct($playlistID, $cacheAgeLimit = 168, $startIndex = 1, $mainCall = true) {
        $this->playList['id'] = "PL" . $playlistID;

        $fileDir = "ytpl_cache/";
        $videoFile = $fileDir . $this->getID() . "_videos.txt";
        $playlistFile = $fileDir . $this->getID() . "_playlist.txt";

        if ($mainCall && file_exists($videoFile) && file_exists($playlistFile) && floor((time() - strtotime(date("F d Y H:i:s", filemtime($videoFile)))) / 3600) < $cacheAgeLimit) {
            $this->videoList = unserialize($this->readFile(($videoFile)));
            $this->playList = unserialize($this->readFile(($playlistFile)));
        } else {
            $xml = simplexml_load_string(file_get_contents('http://gdata.youtube.com/feeds/api/playlists/' . $playlistID . '?max-results=50&start-index=' . $startIndex))
                    or die(".: Error Opening Playlist " . $this->getID() . ", Please check if the playlist ID is valid :.");
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


            if ($mainCall) {
                //  creating sub-playlists and merging them to main playlist as YT allows 50 videos max per request 
                $numReqs = ceil(intVal($this->playList['numVideos']) / 50);
                if ($numReqs > 1) {
                    $temp = array();
                    for ($x = 1; $x < $numReqs; $x++) {
                        $temp[$x] = new YoutubePlayList($playlistID, null, (50 * $x) + 1, false);
                        $this->videoList = array_merge($this->videoList, $temp[$x]->getVideoListArray());
                    }
                }
                // creating cache directory and files
                if ($cacheAgeLimit > 0) {
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

    /* playlist get functions */

    public function getVideoListArray() {
        return $this->videoList;
    }

    public function getVideoByIndex($i) {
        return $this->videoList[$i];
    }

    public function getVideoById($id) {
        foreach ($this->videoList as $video) {
            if ($video->getId() == $id) {
                return $video;
            }
        }
    }

    public function getID() {
        return $this->playList['id'];
    }

    public function getTitle() {
        return $this->playList['title'];
    }

    public function getDescription() {
        return $this->playList['description'];
    }

    public function getNumOfVideos() {
        return $this->playList['numVideos'];
    }

    public function getURL() {
        return "https://www.youtube.com/playlist?list=" . $this->getID();
    }

    /* utility functions */

    public function getJSON() {
        $JSONString = '{"id":"' . $this->getID() . '","title":"' . $this->js($this->getTitle()) .
                '","description":"' . $this->js($this->getDescription()) . '","numVideos":"' . $this->getNumOfVideos() . '","videos":{';
        foreach ($this->videoList as $k => $v) {
            $c = ",";
            reset($this->videoList);
            end($this->videoList);
            if ($k === key($this->videoList)) {
                $c = "";
            }
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

    public function getRSS($config) {
        $config['showNumVideos'] = isset($config['showNumVideos']) ? intval($config['showNumVideos']) : 10;

        $RSSString = '<?xml version="1.0" encoding="UTF-8" ?><rss version="2.0"><channel><title><![CDATA[' . $this->getTitle() .
                ']]></title><link>' . (isset($config['playListURL']) ? $config['playListURL'] : $this->getURL()) . '</link><description><![CDATA[' . $this->getDescription() . ']]></description>';

        foreach ($this->videoList as $k => $v) {
            $RSSString.= '<item><title><![CDATA[' . $v->getTitle() . ']]></title>'
                    . '<description><![CDATA[<img src="' . $v->getThumbnail() . '" /><br />' . $v->getDescription() . ']]></description>'
                    . '<link>' . (isset($config['playListURL']) ? $config['playListURL'] : $v->getUrl()) . '</link>'
                    . '<pubDate>' . $v->getDatePublished() . '</pubDate></item>';
            if ($k == ($config['showNumVideos'] - 1)) {
                break;
            }
        }
        $RSSString.= "</channel></rss>";

        return $RSSString;
    }

    private function writeFile($file, $str) {
        $f = fopen($file, "w");
        fwrite($f, $str);
        fclose($f);
    }

    private function readFile($file) {
        $f = fopen($file, "r") or die("Unable to open file!");
        $contents = fread($f, filesize($file));
        fclose($f);
        return $contents;
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

    public function display($show) {
        print '<div id="yt_plContainer" class="yt_plContainer">';
        $show['playlistTitle'] and
                print '<div class="yt_title">&nbsp;' . $this->getTitle() . '<input type="button" value="Hide" onclick="showHide(\'description\',this)" /></div>';
        $show['playlistDescription'] and print '<div id="description" class="yt_description">' . $this->newLineToBR($this->getDescription()) . '</div>';
        print '<div id="player"><iframe class="yt_player" src="//www.youtube.com/embed/' . $this->videoList[0]->getID() . '?rel=0"  allowfullscreen></iframe></div>';
        if ($show['videoDescription']) {
            print '<div class="yt_title">&nbsp;Video Information<input type="button" value="Hide" onclick="showHide(\'videoInfo\',this)" /></div>';
            print '<div id="videoInfo" class="yt_description">' . $this->newLineToBR($this->videoList[0]->getDescription()) . '</div>';
        }
        print '<div class="yt_title">&nbsp;Playlist';
        $show['playlistVideoCount'] and print ' (' . $this->getNumOfVideos() . ')';
        print '<input type="button" value="Hide" onclick="showHide(\'youtubePl\',this)" /><input type="button" value="Play All"'
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
            print '<tr class="plTracks" onclick="loadVideo(\'' . $video->getID() . "'" . ($show['videoDescription'] ? ",'" . htmlspecialchars($this->js($video->getDescription())) . "'" : "") . ')">';
            print '<th>' . ($i + 1) . '</th>';
            $show['videoImage'] and print '<td class="yt_img"><img alt="" class="yt_img" src=' . $video->getThumbnail() . ' /></td>';
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

}

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
