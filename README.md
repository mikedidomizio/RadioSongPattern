RadioSongPattern
==================

Scans a radio stations last played playlist and puts them into a database

<h2>Purpose:</h2>
I want to see if a pattern can be detected in certain radio stations.

<h2>How to use:</h2>
- There is a SQL file called db.sql.  This sets up the tables
- Create a radio.extended.class.php in the root directory.  Follow the similar format used in the radio.extended.class.php.example file.
This file is supposed to take the html object from SimpleHTMLDom and return songs in a multidimensional array in this format :

```php

[
  [
    'artist'   => 'Taylor Swift',
    'song'     => 'Shake it off',
    'playedAt' => '2015-10-28 15:31:12'
  ]
];


```

As long as it's in that format it should work.

<h2>Requirements:</h2>
- PHP
- MYSQL
- Composer

<h2>Todo:</h2>
- Add more methods to do something useful with the information.  Also, for a current hits station, the music changes as time goes on.  
- Possibly add support more than 1 radio station