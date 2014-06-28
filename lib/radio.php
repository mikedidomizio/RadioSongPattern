<?php
    namespace radio;
    include 'includes/simplehtmldom.php';

    if(file_exists('getSongData.php')) {
        include 'getSongData.php';
    } else {
        echo 'Could not find getSongData.php';
        exit;
    };

    class radio {

        private $db = [
            'host' => 'localhost',
            'username' => null,
            'password' => '',
            'db'
        ];
        private $DBH = null;
        private $debug = [];
        private $config = null;

        public function __construct($configFileLocation) {

            if(file_exists($configFileLocation)) {
                $this->config = json_decode(file_get_contents($configFileLocation));

                if(isset($this->config->db)) {

                    $this->checkDbConnection($this->config->db);
                    $html = file_get_html($this->config->url);

                    $time = $this->getLastRunTime();

                    if(function_exists('getSongData')) {

                        # all that matters is that this returns the songs
                        $songs = getSongData($html, $time);

                        if(isset($songs) && is_array($songs)) {
                            // insert the songs
                            foreach($songs as $var) {
                                $songId = $this->insertSong($var['artist'], $var['song']);
                                if(!$songId) {
                                    # already in db
                                    $songId = $this->getSongId($var['artist'], $var['song']);
                                };

                                if($songId) {
                                    $this->createHistoryLog($songId, $var['playedAt']);
                                };
                            };
                        }
                    } else {
                        # could not find the getSongData function
                    }
                };
            } else {
                 # Could not find config file
            };
        }

        private function checkDbConnection($db) {
            try {
                $this->DBH = new \PDO("mysql:host=$db->host;dbname=$db->name", $db->user, $db->pass);
            } catch(Exception $e) {}
        }

        private function getSongId($artist, $song) {

            $sql = 'SELECT song_id FROM songs WHERE artist = ? AND song = ?';
            $STH = $this->DBH->prepare($sql);
            $STH->execute(array(
                $artist, $song
            ));
            $rows = $STH->fetchAll(\PDO::FETCH_ASSOC);
            return (!empty($rows)) ? $rows[0] : false;
        }

        private function createHistoryLog($songId, $playedAt) {

            $sql = 'INSERT INTO history (number_of_songs) VALUES (?)';

            $STH = $this->DBH->prepare($sql);
            $STH->execute(array(
                $numberOfSongs
            ));
        }

        private function insertSong($artist, $song) {

            $artist = trim($artist);
            $song = trim($song);
            $sql = 'INSERT INTO songs (artist, song) VALUES (?,?) ON DUPLICATE KEY UPDATE song = song';

            $STH = $this->DBH->prepare($sql);
            $STH->execute(array(
                $artist, $song
            ));

            return $this->DBH->lastInsertId();
        }

        private function getLastRunTime() {

            $sql = 'SELECT playedAt FROM history ORDER BY playedAt DESC LIMIT 1';

            $STH = $this->DBH->prepare($sql);
            $STH->execute();
            $rows = $STH->fetchAll(\PDO::FETCH_ASSOC);

            return (!empty($rows)) ? $rows[0]['time'] : '0000-00-00 00:00:00';
        }
    }