<!DOCTYPE HTML>
<html>
    <head>
        <title>Radio Song Pattern</title>
        <link rel="stylesheet" type="text/css" href="assets/example.css" />
    </head>
    <body>

        <a href="https://github.com/mikedidomizio/RadioSongPattern"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_orange_ff7600.png"></a>

        <?php
            require '../radio.extended.class.php';
            $radio = new \radioStation\radioStation();

            $time = $radio->getLongestTimeWithoutMusic();
            $most = $radio->getMostPlayedSongs(20);
            $artist = $radio->artistWithMostSongs(20);
            $mostPlayedSongs = $radio->getMostPlayedSongs(20);
        ?>
        <h1>Radio Song Pattern</h1>

        <table>
            <tr>
                <th>Website</th>
            </tr>
            <tr>
                <td><?=$radio->getWebsiteURL();?></td>
            </tr>
        </table>

        <table>
            <tr>
                <th>Number of different songs played</th>
            </tr>
            <tr>
                <td><?=$radio->getNumberOfPlayedSongs();?></td>
            </tr>
        </table>

        <table>
            <tr>
                <th>Longest time without music</th>
                <th>Song 1</th>
                <th>Song 2</th>
            </tr>
            <tr>
                <td><?=$time[0];?></td>
                <td><?=$time[1]['played_at'].' '.$time[1]['songData']['artist'].' - '.$time[1]['songData']['song']; ?></td>
                <td><?=$time[2]['played_at'].' '.$time[2]['songData']['artist'].' - '.$time[2]['songData']['song']; ?></td>
            </tr>
        </table>

        <table>
            <tr>
                <th>Artists with most songs played</th>
                <th># of songs</th>
            </tr>
            <?php
                foreach($artist as $var) {
                    ?>
                    <tr>
                        <td><?=$var['artist'];?></td>
                        <td><?=$var['total'];?></td>
                    </tr>
                    <?php
                }
            ?>
        </table>

        <table>
            <tr>
                <th>Most played songs</th>
                <th># of times played</th>
                <th>Average time between plays</th>
                <th>Last played</th>
                <th>Date added</th>
            </tr>
            <?php
                foreach($mostPlayedSongs as $var) {

                    $history = $radio->getSongHistory($var['song_id']);
                    $avg = $radio->getAverageTimeBetweenPlays($history);

                    if($avg !== null) {
                        $avg = gmdate("H:i:s", $avg);
                    }

                    ?>
                    <tr>
                        <td><?=$var['artist'].' - '.$var['song'];?></td>
                        <td><?=$var['total'];?></td>
                        <td><?=$avg;?></td>
                        <td><?=$var['last_played'];?></td>
                        <td><?=$var['date_added'];?></td>
                    </tr>
                    <?php
                }
            ?>
        </table>

    </body>
</html>