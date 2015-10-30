<?php
    namespace radio;
    include '../includes/simplehtmldom.php';

    class radio {

        // this is not used, but in your extended class you should have these variables
        private $db = [
            'host' => 'localhost',
            'user' => null,
            'pass' => '',
            'name' => null
        ];

        private $DBH = null;

        /**
         * Constructor connects to database
         *
         * @param $db   array   parameters used to connect to the database
         */
        protected function __construct($db) {
            
            $this->connectDB($db);
        }

        /**
         * Gets song data from extended class
         */
        public function getSongs() {

            $html = file_get_html($this->url);

            $date = $this->getLastRunDate();

            # all that matters is that this returns the songs
            $songs = $this->getSongData($html, $date);

            return $songs;
        }

        /**
         * Inserts songs into database
         *
         * @param $songs    array   An array of songs to insert into the database (artist, song, playedAt)
         */
        public function insertSongsThatWerePlayed($songs) {

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
                die('Song list returned from getSongData() method needs to be an array.  Array keys should be artist, song, playedAt');
            }
        }

        /**
         * Creates a connection to the database
         *
         * @param $db   array   variables associated with connecting to the database
         */
        private function connectDB($db) {

            try {
                $this->DBH = new \PDO("mysql:host=" . $db['host']. ";dbname=" . $db['name'], $db['user'], $db['pass']);
            } catch(Exception $e) {
                die('Error connecting to database');
            }
        }

        /**
         * Returns the song_id of a song that's already been inserted into the database
         * Both artist and song have to match
         *
         * @param $artist   string  artist name ex. Taylor Swift
         * @param $song     string  artist song ex. Shake it off
         * @return int|null
         */
        private function getSongID($artist, $song) {

            $sql = 'SELECT song_id FROM songs WHERE artist = ? AND song = ? LIMIT 1';
            $STH = $this->DBH->prepare($sql);
            $STH->execute([
                $artist, $song
            ]);
            $row = $STH->fetch();
            return count($row) === 2 ? $row[0] : null;
        }

        /**
         * When a certain song was played, we insert it into history
         *
         * @param $songID       int     The song_id from the songs table
         * @param null $time    string  DateTime of when the song began playing
         *
         * @return boolean      Whether was successfully inserted or not
         */
        private function insertIntoHistory($songID, $time = null) {

            $sql = 'INSERT INTO history (song_id, played_at) VALUES (?, ?)';
            $STH = $this->DBH->prepare($sql);
            return $STH->execute(array(
                $songID,
                $time
            ));
        }

        /**
         * Inserts a song into the database
         *
         * @param $artist   string  artist name ex. Taylor Swift
         * @param $song     string  artist song ex. Shake it off
         * @return          int     the song_id when it's inserted
         */
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
