<?php

include 'radio.extended.class.php';

$radio = new \radioStation\radioStation();
$songs = $radio->retrieveSongDataFromURL();
$radio->insertSongsThatWerePlayed($songs);
