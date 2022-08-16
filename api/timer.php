<?php

/**
 * Checks if the session is still allowed to run
 * 
 * @global $conn db connection
 * @param string $id sessionID of the session to check
 * @return bool false if session is not allowed to run, true if session is still valid
 */
function checkGameTime($id)
{
    // fetch session information
    try {
        global $conn;
        $query = $conn->prepare("SELECT `session_running`, `session_starttime` FROM `sessions` WHERE `session_id`=:sesid");
        $query->execute([
            "sesid" => $id
        ]);
        $response = $query->fetch(PDO::FETCH_ASSOC);
        $running = $response['session_running'];
        $startTime = $response['session_starttime'];

        if (!$running) {
            return false;
        }

        // check if session is still allowed to run
        $gameDuration = 15 * 60; // MINUTES * SECONDS (unixtime is in seconds)
        $timeRemainder = ($startTime + $gameDuration) - time();
        if ($timeRemainder <= 0) {
            // set the run variable to false if time has expired, no more actions can be taken
            $query = $conn->prepare("UPDATE `sessions` SET `session_running`=:srun WHERE `session_id`=:sesid");
            $query->execute([
                "sesid" => $id,
                "srun" => 0
            ]);
            // delete the session cookie
            // setcookie("sessionID", "", time() - 3600, "/");
            return false;
        } else {
            return true;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo "Session not found";
        exit();
    }
}
