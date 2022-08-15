<?php

include('db.php');
include('timer.php');
include('error.php');

if (isset($_GET['new'])) {
    newSession();
} elseif (isset($_GET['get'])) {
    getSession();
} elseif (isset($_GET['join'])) {
    joinSession();
} elseif (isset($_GET['leaderboard'])) {
    getLeaderboard();
} else {
    sendErrorMessage(404, "Endpoint not found");
    exit();
}

function newSession()
{
    // check if no session is present yet
    if (isset($_COOKIE['sessionID'])) {
        sendErrorMessage(400, "Session already started");
        exit();
    }

    // get and check the session name
    $name = htmlspecialchars($_GET['new']);
    if ($name == "") {
        sendErrorMessage(400, "Empty session name");
        exit();
    }

    // generate the random join pin
    $pin = "";
    for ($i = 0; $i < 4; $i++) {
        $pin .= rand(0, 9);
    }

    // generating the sessionID
    $hash = $name . $pin . time();
    $id = md5($hash);

    // insert the session into db
    global $conn;
    $query = $conn->prepare("INSERT INTO `sessions` (`session_id`, `session_name`, `session_pin`, `session_running`, `session_starttime`) VALUES (:sesid, :sname, :spin, :srun, :sst)");
    $query->execute([
        "sesid" => $id,
        "sname" => $name,
        "spin" => $pin,
        "srun" => 1,
        "sst" => time()
    ]);

    // get the random products
    $query = $conn->prepare("SELECT * FROM `products` ORDER BY RAND() LIMIT 9");
    $query->execute();
    $products = $query->fetchAll(PDO::FETCH_ASSOC);

    // format products string
    $prodstr = "";
    foreach ($products as $product) {
        $prodstr .= $product['id'] . ",";
    }
    $prodstr = substr($prodstr, 0, -1);

    // insert products into db
    $query = $conn->prepare("UPDATE `sessions` SET `session_products`=:prods WHERE `session_id`=:sesid");
    $query->execute([
        "sesid" => $id,
        "prods" => $prodstr
    ]);

    // respond with json
    $json = array(
        "sessionID" => $id,
        "sessionName" => $name
    );
    // set the sessionID cookie
    setcookie("sessionID", $id, time() + 86400, "/");
    header('Content-Type: application/json');
    echo json_encode($json);
    exit();
}

function getSession()
{
    // check if session token is present
    if (isset($_COOKIE['sessionID'])) {

        // try fetching the session
        try {
            // try to fetch the session with given ID
            $id = htmlspecialchars($_COOKIE['sessionID']);
            global $conn;
            $query = $conn->prepare("SELECT * FROM `sessions` WHERE `session_id`=:sesid");
            $query->execute([
                "sesid" => $id
            ]);
            $response = $query->fetch(PDO::FETCH_ASSOC);

            // check if the session is still valid
            if (checkGameTime($id)) {
                // return the session information
                $json = array(
                    "sessionID" => $response['session_id'],
                    "sessionName" => $response['session_name'],
                    "sessionPin" => $response['session_pin'],
                    "sessionStartTime" => $response['session_starttime'],
                    "sessionRunning" => $response['session_running'],
                    "sessionProducts" => $response['session_products'],
                    "sessionFoundProducts" => $response['session_found'],
                    "sessionHints" => $response['session_hints']
                );
                header('Content-Type: application/json');
                echo json_encode($json);
            } else {
                sendErrorMessage(400, "Session has expired");
                exit();
            }
        } catch (Exception $e) {
            // catch errors and return
            sendErrorMessage(400, "No session with this sessionID");
            exit();
        }
    } else {
        // return error when no sessionID token is present
        sendErrorMessage(400, "No sessionID token");
        exit();
    }
}

function joinSession()
{
    // mitigate brute-force by time-out of 250ms (crude implementation)
    usleep(250000);

    // check if no session is present yet
    if (isset($_COOKIE['sessionID'])) {
        sendErrorMessage(400, "Session already started");
        exit();
    }

    // get and check the session name
    $name = htmlspecialchars($_GET['join']);
    if ($name == "") {
        sendErrorMessage(400, "Empty session name");
        exit();
    }

    // get and check the session pin
    $pin = htmlspecialchars($_GET['pin']);
    if ($pin == "") {
        sendErrorMessage(400, "Empty pin");
        exit();
    }

    // try joining the session
    try {
        // fetch the session variables
        global $conn;
        $query = $conn->prepare("SELECT `session_id`, `session_pin` FROM `sessions` WHERE `session_name`=:sname");
        $query->execute([
            "sname" => $name
        ]);
        $response = $query->fetch(PDO::FETCH_ASSOC);
        $id = $response['session_id'];
        $checkPin = $response['session_pin'];

        // test if the correct pin is passed
        if ($pin == $checkPin) {
            // pin is matching, join session
            // respond with json
            $json = array(
                "sessionID" => $id,
                "sessionName" => $name
            );
            // set the sessionID cookie
            setcookie("sessionID", $id, time() + 86400, "/");
            header('Content-Type: application/json');
            echo json_encode($json);
            exit();
        } else {
            sendErrorMessage(401, "Session pin is not correct");
            exit();
        }
    } catch (Exception $e) {
        sendErrorMessage(404, "Session does not exist");
        exit();
    }
}

function getLeaderboard()
{
    // fetch the sessions
    global $conn;
    $query = $conn->prepare("SELECT `session_name`,`session_running`,`session_starttime`,`session_found`,`session_hints` FROM `sessions`");
    $query->execute([]);
    $response = $query->fetchAll(PDO::FETCH_ASSOC);

    // make storage arrays for the sessions
    $runningSessions = array();
    $stoppedSessions = array();

    // loop through all sessions
    foreach ($response as $session) {
        $fprod = count(explode(",", $session['session_found']));
        $hprod = count(explode(",", $session['session_hints']));
        $running = $session['session_running'];

        $sesinfo = array(
            "sessionName" => $session['session_name'],
            "sessionStartTime" => $session['session_starttime'],
            "sessionFoundProducts" => $fprod,
            "sessionHints" => $hprod
        );

        // push the session to the corresponding array
        if ($running) {
            array_push($runningSessions, $sesinfo);
        } else {
            array_push($stoppedSessions, $sesinfo);
        }
    }

    // respond with json
    $json = array(
        "runningSessions" => $runningSessions,
        "stoppedSessions" => $stoppedSessions
    );
    // set the sessionID cookie
    header('Content-Type: application/json');
    echo json_encode($json);
    exit();
}
