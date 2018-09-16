<?php

    $authorised  = false;

    include 'functions/createLog.php';

    include 'functions/main.php';

    include 'functions/config.php';

    include 'functions/getLikeCount.php';

    include 'functions/monzo/initMonzoAuth.php';
    include 'functions/monzo/getAccountDetails.php';
    include 'functions/monzo/refreshAccessToken.php';
    include 'functions/monzo/depositMoney.php';

    $config      = configSettings();

    if ( !empty( $config['accessToken'] ) ) {

        $authorised = true;

        createLog('Access Token found in db, moving on...');

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