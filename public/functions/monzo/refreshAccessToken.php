<?php

function refreshMonzo( $config ) {

    createLog('Attempting to refresh Monzo Auth...');

    $ch = curl_init();
    $authUrl = $config['monzoUrl'] . '/oauth2/token';
    curl_setopt($ch, CURLOPT_URL, $authUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,
             http_build_query(
                    [
                        'grant_type'    => 'refresh_token',
                        'client_id'     => $config['clientId'],
                        'client_secret' => $config['clientSecret'],
                        'refresh_token' => $config['refreshToken'],
                    ]
                )
            );
    // Receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);
    $response      = json_decode($server_output, true);

    // debug('response from refresh:');
    // debug($response);

    $to      = 'hello@jonashcroft.co.uk';
    $headers = 'From: hello@jonashcroft.co.uk' . "\r\n" .
    'Reply-To: hello@jonashcroft.co.uk' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

    if ( !empty( $response['refresh_token'] ) ) {

        $con = mysqli_connect( $config['dbHost'], $config['dbUser'], $config['dbPass'], $config['dbName'] );

        // Check connection
        if ( !$con ) {
            die( 'Connection failed: ' . mysqli_connect_error() );
        }

        $refreshToken = $response['refresh_token'];
        $accessToken  = $response['access_token'];

        $sql          = "UPDATE creds SET refreshToken='$refreshToken', accessToken='$accessToken' WHERE id=1";

        if ( mysqli_query( $con, $sql ) ) {

            // echo "tokens refreshed successfully.";

            createLog('Monzo Tokens refreshed succesfully...');

            $subject = 'tokens refreshed successfully';
            $message = 'good job b';

            // GOTO DEPOSIT
            depositMoney( $config );


        } else {

            echo "Error: " . $sql . "<br>" . mysqli_error($con);

            createLog( 'MySQL failure when attempting to refresh tokens..' . $sql . ' - ' . mysqli_error($con) );

            $subject = 'tokens did not refresh due to mysql';
            $message = 'Error: ' . json_decode($sql, true) . '<br>' . mysqli_error($con);

        }

        mysqli_close( $con );

    }
    else {

        createLog('No refresh tokens from Monzo...');

        $subject = 'tokens did not refresh due to monzo error';
        $message = 'Error: ' . $response;

    }


    mail($to, $subject, $message, $headers);

}