<?php

function refreshMonzo( $config ) {

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

    debug($response);

    if ( !empty( $response['refresh_token'] ) ) {

        $conn = mysqli_connect( $config['dbName'], $config['dbUser'], $config['dbPass'], $config['dbName'] );

        // Check connection
        if ( !$conn ) {
            die( 'Connection failed: ' . mysqli_connect_error() );
        }

        $refreshToken = $response['refresh_token'];
        $accessToken  = $response['access_token'];

        $sql          = "UPDATE creds SET refreshToken='$refreshToken', accessToken='$accessToken' WHERE id=1";


        if ( mysqli_query( $conn, $sql ) ) {
            echo "tokens refreshed successfully.";

            // GOTO DEPOSIT
            depositMoney( $config );


        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }

        mysqli_close( $conn );

    }

}