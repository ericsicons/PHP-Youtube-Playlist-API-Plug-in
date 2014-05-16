<?php
require 'ytpl_php.php';
$playlist = new YoutubePlayList($playlistID = "nqdTIS_B64I7zbB_tPgvHiFTnmIqpT0u", $cacheAge = 0);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>JSON Demo</title>
        <meta charset="UTF-8">
    </head>
    <body>
        <h3>Example of how to extract and display the playlist data using JSON</h3>
        <script type="text/javascript">
            var playlist = JSON.parse('<?php echo $playlist->getJSON(); ?>');
            document.write("<b>Playlist ID : </b>" + playlist.id + "<br />");
            document.write("<b>Playlist Title : </b>" + playlist.title + "<br />");
            document.write("<b>Playlist Description : </b>" + playlist.description + "<br />");
            document.write("<b>Playlist Videos : </b>" + playlist.numVideos + "<br />");
            document.write("<br /><strong>List of videos</strong> <br /><br />");

            document.write("<table style='border:1px solid black'><tr><th style='border-bottom:1px solid black'>Index</th>");
            for (var videoData in playlist.videos[0]) {
                document.write("<th style='border-bottom:1px solid black'>" + videoData + " </th>");
            }
            document.write("</th></tr>")
            for (var videoIndex in playlist.videos) {
                document.write("<tr><td style='border-bottom:1px solid black'>" + videoIndex + "</td>");
                for (var videoData in playlist.videos[videoIndex]) {

                    document.write("<td style='border-bottom:1px solid black'>" + playlist.videos[videoIndex][videoData] + " </td>");
                }
                document.write("</tr>");

            }
            document.write("</table>");

        </script>
    </body>
</html>
