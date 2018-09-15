<?php

function createLog( $logMessage ) {

    $logFilePath = 'logs/cron-log.txt';
    $fullLog     = '[' . date('Y-m-d H:m:s') . '] ' . $logMessage;

    file_put_contents( $logFilePath, $fullLog . '\n', FILE_APPEND );

}