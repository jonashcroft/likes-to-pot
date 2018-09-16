<?php

function initMonzoAuth( $config ) {

    $state = uniqid( strtotime('now') . '-' . rand(1,999999) , true ); ?>

    <a href="https://auth.monzo.com/?client_id=<?php echo $config['clientId']; ?>&redirect_uri=<?php echo $config['redirectUri']; ?>&response_type=code&state=<?php echo $state; ?>">Auth Monzo</a>

<?php
    if ( ! empty( $_GET['code'] ) ) {

        $authCode = $_GET['code'];

        $ch = curl_init();
        $authUrl = $config['monzoUrl'] . '/oauth2/token';
        curl_setopt($ch, CURLOPT_URL, $authUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
                http_build_query(
                        [
                            'grant_type'    => 'authorization_code',
                            'client_id'     => $config['clientId'],
                            'client_secret' => $config['clientSecret'],
                            'redirect_uri'  => $config['redirectUri'],
                            'code'          => $authCode,
                        ]
                    )
                );
        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);
        $response      = json_decode($server_output, true);

        if ( !empty( $response['access_token'] ) ) {

            echo '<pre>';
            print_r($response);
            echo '</pre>';

            $con = mysqli_connect( $config['dbHost'], $config['dbUser'], $config['dbPass'], $config['dbName'] );

            if ( !$con ) {
                die("Connection failed: " . mysqli_connect_error());
            }
            else {

                $refreshToken = $response['refresh_token'];
                $accessToken  = $response['access_token'];
                $userId       = $response['user_id'];

                $sql          = "UPDATE creds SET userID='$userId', refreshToken='$refreshToken', accessToken='$accessToken' WHERE id=1 ";

                if ( mysqli_query( $con, $sql ) ) {
                    echo 'Credentials saved successfully.';

                    // Lol :/
                    echo '<script>location.href = ' . $config['redirectUri'] . ';</script>';

                } else {
                    echo 'Error: ' . $sql . '<br>' . mysqli_error($con);
                }

                mysqli_close($con);

            }

        }
        else {

            echo '<pre>';
            print_r($response);
            echo '</pre>';

        }
        curl_close($ch);
    }

}