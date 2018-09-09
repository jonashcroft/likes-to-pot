<?php

    include 'functions/main.php';

    function configSettings() {

        $config = [];

        return $config;

    }

    $monzoUrl     = 'https://api.monzo.com';
    $redirectUri  = 'http://likestopot.test';
    $state        = uniqid( strtotime('now') . '-' . rand(1,999999) , true );

    $authorised   = false;

    $refreshToken = '';
    $accessToken  = '';
    $userId       = '';
    $monzoPot     = '';


    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = 'root';
    $dbName = 'scotchbox';

    $con = mysqli_connect( $dbName, $dbUser, $dbPass, $dbName );

    if ( mysqli_connect_errno() ) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

    $sql = "SELECT * FROM creds";

    if ( $result = mysqli_query( $con, $sql ) ) {

      // Fetch one and one row
        while ( $row = mysqli_fetch_row( $result ) ) {

            $clientId     = $row[1];
            $clientSecret = $row[2];
            $userId       = $row[3];
            $refreshToken = $row[4];
            $accessToken  = $row[5];
            $accountId    = $row[6];
            $monzoPot     = $row[7];

        }

        mysqli_free_result($result);
    }

    mysqli_close($con);

    if ( !empty( $accessToken ) ) {

        $authorised = true;

    }

/*-------------
Get todays 'liked' tweets from a Google Sheet populatd via IFTTT
--------------*/
function getTodaysLikes() {

    $today       = date('Y-m-d');
    $url         = 'https://spreadsheets.google.com/feeds/list/1vqErLZzyKhRZiufJ-YSp-OwbgaOMZgPqHwBdnx24dTY/od6/public/values?alt=json';
    $json        = file_get_contents($url);
    $data        = json_decode($json, true);
    $todaysLikes = 0;

    foreach ( $data['feed']['entry'] as $item ) {

        $likedTimestamp = strtotime( $item['gsx$_ciyn3']['$t'] );
        $likedDate      = date( 'Y-m-d', $likedTimestamp );

        if ( $likedDate == $today ) {
            $todaysLikes++;
        }
    }

    return $todaysLikes;

}


function refreshMonzo( $monzoUrl, $clientId, $clientSecret, $refreshToken ) {

    $ch = curl_init();
    $authUrl = $monzoUrl . '/oauth2/token';
    curl_setopt($ch, CURLOPT_URL, $authUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,
             http_build_query(
                    [
                        'grant_type'    => 'refresh_token',
                        'client_id'     => $clientId,
                        'client_secret' => $clientSecret,
                        'refresh_token' => $refreshToken,
                    ]
                )
            );
    // Receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);
    $response      = json_decode($server_output, true);

    debug($response);

    if ( !empty( $response['refresh_token'] ) ) {

        $conn = mysqli_connect($dbName, $dbUser, $dbPass, $dbName);

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


        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        }

        mysqli_close( $conn );

    }

}


if ( !$authorised ) { ?>

    <a href="https://auth.monzo.com/?client_id=<?php echo $clientId ?>&redirect_uri=<?php echo $redirectUri ?>&response_type=code&state=<?php echo $state ?>">Auth Monzo</a>

    <?php

        if ( ! empty( $_GET['code'] ) ) {

            $authCode = $_GET['code'];

            $ch = curl_init();
            $authUrl = $monzoUrl . '/oauth2/token';
            curl_setopt($ch, CURLOPT_URL, $authUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,
                     http_build_query(
                            [
                                'grant_type'    => 'authorization_code',
                                'client_id'     => $clientId,
                                'client_secret' => $clientSecret,
                                'redirect_uri'  => $redirectUri,
                                'code'          => $authCode,
                            ]
                        )
                    );
            // Receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $server_output = curl_exec($ch);
            $response = json_decode($server_output, true);

            if ( !empty( $response['access_token'] ) ) {

                echo '<pre>';
                print_r($response);
                echo '</pre>';

                $conn = mysqli_connect($dbName, $dbUser, $dbPass, $dbName);

                // Check connection
                if ( !$conn ) {
                    die( 'Connection failed: ' . mysqli_connect_error() );
                }

                $refreshToken = $response['refresh_token'];
                $accessToken  = $response['access_token'];
                $userId       = $response['user_id'];

                $sql          = "UPDATE creds SET userID='$userId', refreshToken='$refreshToken', accessToken='$accessToken' WHERE id=1 ";

                // $sql = "INSERT INTO creds (userID, refreshToken, accessToken)
                // -- VALUES ('$userId', '$refreshToken', '$accessToken')";

                if ( mysqli_query( $conn, $sql ) ) {
                    echo "Credentials saved successfully."; ?>

                    <script>location.href = '<?php echo $redirectUri ?>';</script>

                <?php

                    } else {
                        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
                    }

                mysqli_close( $conn );

            }
            else {

                echo '<pre>';
                print_r($response);
                echo '</pre>';

            }
            curl_close ($ch);
        }

    ?>


<?php } else {

    $authorization = 'Authorization: Bearer ' . $accessToken;

    if ( empty( $accountId)  ) {

        $ch = curl_init();

        $authUrl = $monzoUrl . '/accounts';
        curl_setopt($ch, CURLOPT_URL, $authUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $result     = curl_exec($ch);
        $httpcode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $accounts = json_decode($result, true);

        if ( !empty( $accounts ) ) {

            $conn = mysqli_connect($dbName, $dbUser, $dbPass, $dbName);

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

                    } else {

                        echo "Error: " . $sql . "<br>" . mysqli_error($conn);

                    }

                }
            }

            mysqli_close($conn);

        }
    }

    else {
        $authorization = 'Authorization: Bearer ' . $accessToken;

        // CHECK IF USER HASN'T ALREADY INSERTED TODAY
        $todaysDate = date('Y-m-d');

        $con = mysqli_connect($dbName, $dbUser, $dbPass, $dbName);

        $query = "SELECT * FROM transHistory WHERE depositdate = '$todaysDate'";
        $result = mysqli_query($con, $query);

        if ( mysqli_num_rows($result) > 0) {

            echo "already done today b";

            mysqli_close($con);

        }
        else {

            mysqli_close($con);

            echo 'no deposit on this date';

            $tweetsLiked = 1;
            $tweetsLiked = getTodaysLikes();

            $depositAmnt = 1 * $tweetsLiked;

            if ( $tweetsLiked > 0 ) {

                $dedupeId = 'tweet-2-pots-' . md5( strtotime('now') .'-' . rand(1000,2000) );
                $url         = $monzoUrl . '/pots/' . $monzoPot . '/deposit';
                $data        = [
                                'amount'            => $depositAmnt,
                                'source_account_id' => $accountId,
                                'dedupe_id'         => $dedupeId
                                ];
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
                curl_setopt($ch, CURLOPT_HTTPHEADER,
                        [
                            $authorization, 'Content-Type: application/x-www-form-urlencoded'
                        ]
                    );
                $depositResponse = curl_exec($ch);
                $httpcode        = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $depositResult   = json_decode($depositResponse, true);

                curl_close($ch);

                if ( $err ) {

                    debug("cURL Error #:" . $err);

                } else {

                    debug( $httpcode );
                    debug( $depositResult );

                    switch ( $httpcode ) {

                        // Reauthorize
                        case '401':

                            refreshMonzo( $monzoUrl, $clientId, $clientSecret, $refreshToken );

                            break;

                        // Reauthorize
                        case '400':

                            refreshMonzo( $monzoUrl, $clientId, $clientSecret, $refreshToken );

                            break;

                        case '200':

                            $newBalance = $depositResult['balance'];

                            $conn = mysqli_connect($dbName, $dbUser, $dbPass, $dbName);

                            // Check connection
                            if ( !$conn ) {
                                die("Connection failed: " . mysqli_connect_error());
                            }

                            $date = date('Y-m-d');

                            $sql = "INSERT INTO transHistory (depositdate, likes, new_balance)
                            VALUES ('$date', '$tweetsLiked', '$newBalance')";

                            if ( mysqli_query( $conn, $sql ) ) {
                                echo "New record created successfully";
                            } else {
                                echo "Error: " . $sql . "<br>" . mysqli_error($conn);
                            }

                            mysqli_close($conn);

                            break;

                        default:

                            debug('cURL for pot deposit failed ' . $httpcode);

                            break;

                    }

                }

            }

        }

    }

}


?>