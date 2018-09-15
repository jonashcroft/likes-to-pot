<?php

/*-------------
Get count for the previous days likes

getTodaysLikes will grab the total likes, so we can subtract 'today' from 'yesterday'
to get the total for the current day.

Ideally the Google Sheet would add the Timestamp for when the 'Like' was added
and we'd just go 'IF ADD DATE == TODAY' - but IFTTT/Google seem to update ALL of the
Timestamp cells to the current timestamp any time a new row is added.

There is probably a way to 'only timestamp new rows' but this is already messy.
--------------*/
function getYesterdaysLikes( $config ) {

    $yesterdaysDate = date( 'Y-m-d' , strtotime('-1 days') );

    $con            = mysqli_connect( $config['dbName'], $config['dbUser'], $config['dbPass'], $config['dbName'] );
    $query          = "SELECT likeCount FROM transHistory WHERE depositDate = '$yesterdaysDate'";
    $result         = mysqli_query($con, $query);

    if ( mysqli_num_rows($result) > 0) {
        while ( $row = mysqli_fetch_row( $result ) ) {
            $yesterdaysLikes = $row[0];
        }
    }

    mysqli_close($con);

    return $yesterdaysLikes;
}


/*-------------
Get todays 'liked' tweets from a Google Sheet populatd via IFTTT

(lol RIP TWitter API)
--------------*/
function getTodaysLikes( $config ) {

    $url             = 'https://spreadsheets.google.com/feeds/list/1vqErLZzyKhRZiufJ-YSp-OwbgaOMZgPqHwBdnx24dTY/od6/public/values?alt=json';
    $json            = file_get_contents($url);
    $data            = json_decode($json, true);

    $allTimeLikes    = 0;

    $yesterdaysLikes = getYesterdaysLikes( $config );
    $todaysLikes     = 0;

    foreach ( $data['feed']['entry'] as $item ) {
        $allTimeLikes++;
    }

    $todaysLikes = $allTimeLikes - $yesterdaysLikes;

    // debug('All Time Likes: ' . $allTimeLikes );
    // debug('Yesterdays Likes: ' . $yesterdaysLikes );
    // debug('Todays Likes: ' . $todaysLikes );

    return $todaysLikes;

}