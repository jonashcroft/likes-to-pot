<?php

    include 'functions/main.php';

    include 'functions/getLikeCount.php';

    include 'functions/monzo/initMonzoAuth.php';

    include 'functions/monzo/getAccountDetails.php';
    include 'functions/monzo/refreshAccessToken.php';
    include 'functions/monzo/depositMoney.php';

    $authorised   = false;

    function configSettings() {

        $refreshToken = '';
        $accessToken  = '';
        $userId       = '';
        $monzoPot     = '';


        $config = [
            'monzoUrl'    => 'https://api.monzo.com',
            'redirectUri' => 'http://likestopot.test',

            'dbHost'      => 'localhost',
            'dbUser'      => 'root',
            'dbPass'      => 'root',
            'dbName'      => 'scotchbox',
        ];

        $con = mysqli_connect( $config['dbHost'], $config['dbUser'], $config['dbPass'], $config['dbName'] );

        if ( mysqli_connect_errno() ) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        $sql = "SELECT * FROM creds";

        if ( $result = mysqli_query( $con, $sql ) ) {

          // Fetch one and one row
            while ( $row = mysqli_fetch_row( $result ) ) {

                $config['clientId']     = $row[1];
                $config['clientSecret'] = $row[2];
                $config['userId']       = $row[3];
                $config['refreshToken'] = $row[4];
                $config['accessToken']  = $row[5];
                $config['accountId']    = $row[6];
                $config['monzoPot']     = $row[7];

            }

            mysqli_free_result($result);
        }

        mysqli_close($con);

        return $config;

    }

    $config = configSettings();

    debug($config);


if ( !empty( $config['accessToken'] ) ) {

    $authorised = true;

}


if ( !$authorised ) {

    initMonzoAuth( $config );

}
else {

    if ( empty( $config['accountId'] )  ) {

        getAccountDetails( $config );

    }

    else {

        depositMoney( $config );

    }

}


?>