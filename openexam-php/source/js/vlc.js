// 
// Support functions for the VideoLAN (VLC) media player plugin. Supports
// VLC version >= 0.8.5 (using the new unified javascript API).
// 
// Author: Anders Lövgren
// Date:   2010-05-10
//  

function play()
{
        var vlc = document.getElementById("vlc");
        vlc.playlist.play();
}

function pause()
{
        var vlc = document.getElementById("vlc");
        vlc.playlist.togglePause()
}

function stop()
{
        var vlc = document.getElementById("vlc");
        vlc.playlist.stop();
}

function forward()
{
        var vlc = document.getElementById("vlc");
        vlc.input.time = vlc.input.time + 5000;  // Seek forward 5 sec
}

function backward()
{
        var vlc = document.getElementById("vlc");
        vlc.input.time = vlc.input.time - 5000;  // Seek backward 5 sec
}

function fullscreen()
{
        var vlc = document.getElementById("vlc");
        vlc.video.toggleFullscreen()
}
