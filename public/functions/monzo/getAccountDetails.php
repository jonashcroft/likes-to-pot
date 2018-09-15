<?php

    function getAccountDetails( $config ) {

        $authorization = 'Authorization: Bearer ' . $config['accessToken'];

        $ch = curl_init();

        $authUrl = $config['monzoUrl'] . '/accounts';
        curl_setopt($ch, CURLOPT_URL, $authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $result     = curl_exec($ch);
        $httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $accounts = json_decode($result, true);

        if ( !empty( $accounts ) ) {

            $conn = mysqli_connect( $config['dbName'], $config['dbUser'], $config['dbPass'], $config['dbName'] );

            // Check connection
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }

            foreach ( $accounts['accounts'] as $key => $account ) {

                if ( $account['type'] == 'uk_retail' ) {

                    $accountId = $account['id'];

                    $sql = "UPDATE creds SET accountId='$accountId' WHERE id=1";

                    if ( mysqli_query( $conn, $sql ) ) {

                        echo "account ID created successfully";

                        depositMoney( $config );

                    } else {

                        echo "Error: " . $sql . "<br>" . mysqli_error($conn);

                    }

                }
            }

            mysqli_close($conn);

        }

    }