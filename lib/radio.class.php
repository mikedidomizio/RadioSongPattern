<?php
    namespace radio;
    include '../includes/simplehtmldom.php';

    class radio {

        private $db = [
            'host' => 'localhost',
            'user' => null,
            'pass' => '',
            'name' => null
        ];

        private $DBH = null;

        protected function __construct($db) {
            $this->checkDbConnection($db);
        }

        public function getSongs() {

            $html = file_get_html($this->url);

            $date = $this->getLastRunDate();

            # all that matters is that this returns the songs
            $songs = $this->getSongData($html, $date);

            if(is_array($songs)) {
                // insert the songs
                foreach($songs as $var) {
                    $songID = $this->insertSong($var['artist'], $var['song']);

                    if(!$songID) {
                        # already in db
                        $songID = $this->getSongID($var['artist'], $var['song']);
                    };

                    # Insert into history
                    $this->insertIntoHistory($songID, $var['playedAt']);
                };

            } else {
                # not an array, so not set up properly
            }
        }

        private function checkDbConnection($db) {

            try {
                $this->DBH = new \PDO("mysql:host=" . $db['host']. ";dbname=" . $db['name'], $db['user'], $db['pass']);
            } catch(Exception $e) {}
        }

        private function getSongID($artist, $song) {

            $sql = 'SELECT song_id FROM songs WHERE artist = ? AND song = ? LIMIT 1';
            $STH = $this->DBH->prepare($sql);
            $STH->execute([
                $artist, $song
            ]);
            $row = $STH->fetch();
            return count($row) === 2 ? $row[0] : null;
        }

        private function insertIntoHistory($songID, $time = null) {

            $sql = 'INSERT INTO history (song_id, played_at) VALUES (?, ?)';
            $STH = $this->DBH->prepare($sql);
            $STH->execute(array(
                $songID,
                $time
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

        /**
         * Returns the last time an entry was put into history
         *
         * @return string
         */
        private function getLastRunDate() {

            $sql = 'SELECT played_at FROM history ORDER BY played_at DESC LIMIT 1';

            $STH = $this->DBH->prepare($sql);
            $STH->execute();
            $row = $STH->fetch();
            $date = count($row) === 2 ? $row[0] : '0000-00-00';
            return new \DateTime($date);
        }
    }
