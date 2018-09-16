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

            debug($accounts);

            $con = mysqli_connect( $config['dbHost'], $config['dbUser'], $config['dbPass'], $config['dbName'] );

            if ( !$con ) {
                createLog('Could not connect to MySQL in getAccountDetails - ' . mysqli_connect_error() );
                die("Connection failed: " . mysqli_connect_error());
            }
            else {

                foreach ( $accounts['accounts'] as $key => $account ) {
                    if ( $account['type'] == 'uk_retail' ) {

                        $accountId = $account['id'];
                        break;

                    }
                }

                $sql = "UPDATE creds SET accountId='$accountId' WHERE id=1";

                if ( mysqli_query( $con, $sql ) ) {

                    echo "account ID created successfully";

                    createLog('Monzo account ID stored successfully.');

                    depositMoney( $config );

                } else {
                    echo "Error: " . $sql . "<br>" . mysqli_error($con);

                    createLog('Monzo account ID store error: ' . $sql . ' - ' . mysqli_error($con) );
                }

            }

            mysqli_close($con);

        }

    }