RadioSongPattern
==================

Scans radio stations last played playlist and puts them into a database, seeing if there is a pattern to see if we can determine the next song

<h2>How to use:</h2>
- Fairly simple right now.  Edit the Config File with the correct parameters
- Create a radio.extended.class.php file in the root directory (A few examples will be created soon)

This file is supposed to take the html object from SimpleHTMLDom and return songs in a multidimensional array in this format :

```php

[
  [
    'artist'   => '2pac ft. Dr. Dre',
    'song'     => 'California Love',
    'playedAt' => '2014-06-28 15:31:12'
  ]
];


```

As long as it's in that format it will continue.  An Example file will come later.

<h2>Requirements</h2>

Requires PHP and MySQL