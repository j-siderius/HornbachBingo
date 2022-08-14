<?php

/**
 * Generates a error response in JSON format
 * 
 * Error messages supported: 200, 201, 202, 400, 401, 404
 * 
 * @param int $status       the status code related to the error
 * @param string $message   the message related to the error
 * @return empty
 */
function sendErrorMessage($status, $message)
{
    $statusErrors = array(
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        400 => "Bad Request",
        401 => "Unauthorized",
        404 => "Not Found"
    );

    $error = $statusErrors[$status];

    header("Content-Type: application/json");
    http_response_code($status);
    echo json_encode(array(
        "status" => $status,
        "error" => $error,
        "message" => $message,
        "timestamp" => date("G:i:s d-j-Y \G\M\TO")
    ));
    return;
}
