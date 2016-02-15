<?php
    namespace radio;

    use Sunra\PhpSimple\HtmlDomParser;

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
        public function retrieveSongDataFromURL() {

            require 'vendor/sunra/php-simple-html-dom-parser/Src/Sunra/PhpSimple/HtmlDomParser.php';

            $html = HtmlDomParser::file_get_html($this->url);

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

            $sql = 'SELECT song_id FROM songs WHERE artist = :artist AND song = :song LIMIT 1';
            $STH = $this->DBH->prepare($sql);
            $STH->bindParam(':artist', $artist, \PDO::PARAM_STR);
            $STH->bindParam(':song', $song, \PDO::PARAM_STR);
            $STH->execute();
            $row = $STH->fetch();

            return count($row) === 2 ? $row[0] : null;
        }

        /**
         * Returns the row for a single song
         *
         * @param $songID   int
         * @return          array
         */
        private function getSongByID($songID = 1) {

            $sql = 'SELECT * FROM songs WHERE song_id = :songID LIMIT 1';

            $STH = $this->DBH->prepare($sql);
            $STH->bindParam(':songID', $songID, \PDO::PARAM_INT);
            $STH->execute();

            return $STH->fetchAll(\PDO::FETCH_ASSOC)[0];
        }

        /**
         * When a certain song was played, we insert it into history
         *
         * @param $songID       int     The song_id from the songs table
         * @param $time         string  DateTime of when the song began playing
         *
         * @return boolean      Whether was successfully inserted or not
         */
        private function insertIntoHistory($songID, $time = null) {

            $sql = 'INSERT INTO history (song_id, played_at) VALUES (:songID, :playedAt)';
            $STH = $this->DBH->prepare($sql);
            $STH->bindParam(':songID', $songID, \PDO::PARAM_INT);
            $STH->bindParam(':playedAt', $time);

            return $STH->execute();
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
            $sql = 'INSERT INTO songs (artist, song) VALUES (:artist, :song) ON DUPLICATE KEY UPDATE song = song';

            $STH = $this->DBH->prepare($sql);
            $STH->bindParam(':artist', $artist, \PDO::PARAM_STR);
            $STH->bindParam(':song', $song, \PDO::PARAM_STR);
            $STH->execute();

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

        /**
         * Returns the artist with the top number of songs played
         *
         * @param  $limit   null|int    The number of results
         * @return          array
         */
        public function artistWithMostSongs($limit = null) {

            $sql = 'SELECT artist, COUNT(artist) total FROM `radio-song-pattern`.songs GROUP BY artist ORDER BY total DESC, artist ASC';

            if($limit !== null && is_int($limit) && $limit > 0) {
                $sql .= ' LIMIT '. $limit;
            }

            $STH = $this->DBH->prepare($sql);
            $STH->execute();
            return $STH->fetchAll(\PDO::FETCH_ASSOC);
        }

        /**
         * Returns a list of the most played songs
         *
         * @param $limit    null|int
         * @return          array
         */
        public function getMostPlayedSongs($limit = null) {

            $sql = 'SELECT COUNT(history.song_id) total, history.song_id, song, artist, date_added, MAX(played_at) last_played FROM `radio-song-pattern`.history AS history LEFT Join `radio-song-pattern`.songs
                    AS songs ON history.song_id = songs.song_id GROUP BY history.song_id ORDER BY total DESC, artist ASC, song ASC';

            if($limit !== null && is_int($limit) && $limit > 0) {
                $sql .= ' LIMIT '. $limit;
            }

            $STH = $this->DBH->prepare($sql);
            $STH->execute();
            return $STH->fetchAll(\PDO::FETCH_ASSOC);
        }

        /**
         * Returns the times a certain song has been played
         *
         * @param $songID   int
         * @return          array
         */
        private function getTimesPlayedByID($songID = 1) {

            $sql = 'SELECT song_id, played_at FROM `radio-song-pattern`.history WHERE song_id = :songID ORDER by played_at DESC';

            $STH = $this->DBH->prepare($sql);
            $STH->bindParam(':songID', $songID, \PDO::PARAM_INT);
            $STH->execute();
            return $STH->fetchAll(\PDO::FETCH_ASSOC);
        }

        /**
         * Returns the website
         *
         * @return string
         */
        public function getWebsiteURL() {
            return $this->url;
        }

        /**
         * Returns the entire history
         *
         * @return array
         */
        private function getHistory() {

            $sql = 'SELECT * FROM `radio-song-pattern`.history ORDER BY played_at';

            $STH = $this->DBH->prepare($sql);
            $STH->execute();
            return $STH->fetchAll(\PDO::FETCH_ASSOC);
        }

        /**
         * Returns the history of a single song
         *
         * @param $songID   int
         * @return array
         */
        public function getSongHistory($songID = 1) {

            $sql = 'SELECT * FROM history WHERE song_id = :songID ORDER BY played_at DESC';

            $STH = $this->DBH->prepare($sql);
            $STH->bindParam(':songID', $songID, \PDO::PARAM_INT);
            $STH->execute();
            return $STH->fetchAll(\PDO::FETCH_ASSOC);
        }

        /**
         * Iterates through songData array
         *
         * NOTE: the dates have to be in order, most recent first
         *
         * @param $songData array
         * @return array
         */
        public function getAverageTimeBetweenPlays($songData) {

            $timeArr = [];
            $count = count($songData);
            $sum = 0;

            for($i = $count - 1; $i > 0; $i--) {
                $timestamp1 = strtotime($songData[$i - 1]['played_at']);
                $timestamp2 = strtotime($songData[$i]['played_at']);

                $diff = $timestamp1 - $timestamp2;

                $timeArr[] = $diff;
                $sum += $diff;
            }

            if($sum !== 0) {
                return $sum / ($count - 1);
            }

            return null;
        }

        /**
         * Returns number of different songs played
         *
         * @return string
         */
        public function getNumberOfPlayedSongs() {

            $sql = 'SELECT COUNT(song_id) total FROM `radio-song-pattern`.songs';

            $STH = $this->DBH->prepare($sql);
            $STH->execute();
            return $STH->fetchALL(\PDO::FETCH_ASSOC)[0]['total'];
        }

        /**
         * Time between two dates
         *
         * @param $date1    date
         * @param $date2    date
         * @return Date object
         */
        private function timeBetween($date1, $date2) {
            $date1 = new \DateTime($date1);
            $date2 = new \DateTime($date2);
            return date_diff($date1, $date2);
        }

        /**
         * Returns an array (time between, previous song, the next song) of the longest time without music
         *
         * @return array
         */
        public function getLongestTimeWithoutMusic() {

            $prev = [];
            $data = [];
            $sql = 'SELECT MAX(diff) AS time
                      FROM (
                          SELECT TIMEDIFF(played_at,@prev) AS diff,
                                 (@prev:=played_at) AS played_at
                            FROM `radio-song-pattern`.history,
                                 (SELECT @prev:=(SELECT MIN(played_at) FROM `radio-song-pattern`.history)) AS init
                           ORDER BY played_at
                       ) AS diffs';

            $STH = $this->DBH->prepare($sql);
            $STH->execute();
            $data[0] = $STH->fetchAll(\PDO::FETCH_ASSOC)[0]['time'];
            $history = $this->getHistory(); // returns the entire history

            foreach($history as $var) {

                if(count($prev) !== 0) {
                    $interval = $this->timeBetween($prev['played_at'], $var['played_at']);

                    if($data[0] === $interval->format('%H:%I:%S')) {
                        $prev['songData'] = $this->getSongByID($prev['song_id']);
                        $var['songData'] = $this->getSongByID($var['song_id']);
                        $data[1] = $prev;
                        $data[2] = $var;
                        break;
                    }
                }

                $prev = $var;
            }

            return $data;
        }
    }
