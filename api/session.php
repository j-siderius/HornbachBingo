<?php

include('db.php');
include('timer.php');
include('error.php');

if (isset($_GET['new'])) {
    newSession();
} elseif (isset($_GET['get'])) {
    getSession();
} else {
    http_response_code(404);
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
    setcookie("sessionID", $id, time() + 86400, "/");
    header('Content-Type: application/json');
    echo json_encode($json);
    exit();
}

function getSession()
{
    // check if session token is present
    if (isset($_COOKIE['sessionID'])) {

        try {
            // try to fetch the session with given ID
            $id = htmlspecialchars($_COOKIE['sessionID']);
            global $conn;
            $query = $conn->prepare("SELECT * FROM `sessions` WHERE `session_id`=:sesid");
            $query->execute([
                "sesid" => $id
            ]);
            $response = $query->fetch(PDO::FETCH_ASSOC);

            // TODO: leave out time checking

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
