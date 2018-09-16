<?php

function depositMoney( $config ) {

    $authorization = 'Authorization: Bearer ' . $config['accessToken'];

    // CHECK IF USER HASN'T ALREADY INSERTED TODAY
    $todaysDate = date('Y-m-d');

    $con = mysqli_connect( $config['dbHost'], $config['dbUser'], $config['dbPass'], $config['dbName'] );

    $query = "SELECT * FROM transHistory WHERE depositDate = '$todaysDate'";
    $result = mysqli_query($con, $query);

    if ( $result = mysqli_query( $con, $query ) ) {

        while ( $row = mysqli_fetch_row( $result ) ) {

            // debug($row);

        }
    }

    if ( mysqli_num_rows($result) > 0) {

        // echo "already done today b";

        createLog('Already deposited for the current day.');

        mysqli_close($con);

        $tweetsLiked = getTodaysLikes( $config );

    }
    else {

        mysqli_close($con);

        // echo 'no deposit on this date';
        createLog('No deposit today, attempting to contact Monzo...');

        $tweetsLiked = 1;
        $tweetsLiked = getTodaysLikes( $config );

        $depositAmnt = 1 * $tweetsLiked;

        if ( $tweetsLiked > 0 ) {

            $dedupeId = 'tweet-2-pots-' . md5( strtotime('now') .'-' . rand(1000,2000) );
            $url         = $config['monzoUrl'] . '/pots/' . $config['monzoPot'] . '/deposit';
            $data        = [
                            'amount'            => $depositAmnt,
                            'source_account_id' => $config['accountId'],
                            'dedupe_id'         => $dedupeId
                            ];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                    [
                        $authorization, 'Content-Type: application/x-www-form-urlencoded'
                    ]
                );
            $depositResponse = curl_exec($ch);
            $httpcode        = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $depositResult   = json_decode($depositResponse, true);

            $err             = curl_errno($ch);

            curl_close($ch);

            if ( $err ) {

                // debug("cURL Error #:" . $err);

                createLog('Deposit: cURL error: ' . $err);

            } else {

                // createLog('Deposit cURL success, moving on...' . $httpcode);


                debug( $httpcode );
                debug( $depositResult );


                switch ( $httpcode ) {

                    // Reauthorize
                    case '401':

                        createLog( 'Deposit cURL error: ' . $httpcode . ': ' . $depositResult['error_description'] );
                        refreshMonzo( $config );

                        break;

                    // Reauthorize
                    case '400':

                        createLog( 'Deposit cURL error: ' . $httpcode . ': ' . $depositResult['error_description'] );
                        refreshMonzo( $config );

                        break;

                    case '200':

                        $newBalance = $depositResult['balance'];

                        $con = mysqli_connect( $config['dbHost'], $config['dbUser'], $config['dbPass'], $config['dbName'] );

                        // Check connection
                        if ( !$conn ) {

                            createLog('MySQL Connection failure ' . mysqli_connect_error() );

                            die("Connection failed: " . mysqli_connect_error());

                        }

                        $date = date('Y-m-d');

                        $sql = "INSERT INTO transHistory (depositDate, likeCount, newBalance)
                        VALUES ('$date', '$tweetsLiked', '$newBalance')";

                        if ( mysqli_query( $con, $sql ) ) {

                            // echo "New record created successfully";

                            createLog('Deposit successfully - you deposited ' . $depositAmnt);

                        } else {

                            // echo "Error: " . $sql . "<br>" . mysqli_error($conn);

                            createLog('MySQL failure: '. $sql . "<br>" . mysqli_error($con));

                        }

                        mysqli_close($con);

                        break;

                    default:

                        // debug('cURL for pot deposit failed ' . $httpcode);

                        createLog('cURL for pot deposit failed ' . $httpcode);

                        break;

                }

            }

        }

    }


}