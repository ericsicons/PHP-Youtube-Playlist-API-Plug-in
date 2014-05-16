/*
 * PHP Youtube Playlist API Plugin JavaScript File, version 1
 */

function loadVideo(id, videoInfo) {
    document.getElementById("player").innerHTML = '<iframe class="yt_player" src="//www.youtube.com/embed/' + id + '?rel=0&autoplay=1" frameborder="0" allowfullscreen></iframe>';
    if (typeof videoInfo !== 'undefined')
        document.getElementById("videoInfo").innerHTML = videoInfo;
    document.getElementById("yt_plContainer").scrollIntoView();
}

function loadPlaylist(id) {
    document.getElementById('player').innerHTML = '<iframe class="yt_player" src="//www.youtube.com/embed/videoseries?list=' + id + '&rel=0&autoplay=1" frameborder="0" allowfullscreen></iframe>';
    document.getElementById("yt_plContainer").scrollIntoView();
}

function showHide(id, button) {
    var _ = document.getElementById(id);
    if (_.style.display !== 'none') {
        _.style.display = "none";
        button.value = "Show";

    } else {
        _.style.display = '';
        button.value = "Hide";
    }
}
