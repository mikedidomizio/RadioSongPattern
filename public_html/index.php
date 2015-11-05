<?php

include '../radio.extended.class.php';

$radio = new \radioStation\radioStation();
$songs = $radio->getSongs();
$radio->insertSongsThatWerePlayed($songs);
