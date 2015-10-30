<?php

include '../lib/radio.class.php';
include '../radio.extended.class.php';

$radio = new \radioStation\radioStation();
$songs = $radio->getSongs();
$radio->insertSongsThatWerePlayed($songs);
