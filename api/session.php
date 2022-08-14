<?php

include('db.php');
include('names.php');
include('timer.php');

if (isset($_GET['new'])) {
    newSession();
} elseif (isset($_GET['get'])) {
    getSession();
} else {
    http_response_code(404);
    exit();
}

# TODO: check if new&get can be combined into one url

function newSession()
{
    // check if no session is present yet
    if (isset($_COOKIE['sessionID'])) {
        http_response_code(400);
        echo "Session already started";
        exit();
    }

    // generate a random name
    // TODO: check if we can use user-generated names
    global $NAMES;
    $i = array_rand($NAMES, 1);
    $name = $NAMES[$i];

    $id = md5($name);
    $time = time();

    // insert the session into db
    global $conn;
    $query = $conn->prepare("INSERT INTO `sessions` (`session_id`, `session_name`, `session_running`, `session_starttime`) VALUES (:sesid, :sname, :srun, :sst)");
    $query->execute([
        "sesid" => $id,
        "sname" => $name,
        "srun" => 1,
        "sst" => $time
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
    $response = array(
        "sessionID" => $id,
        "sessionName" => $name
    );
    setcookie("sessionID", $id, time() + 86400, "/");
    header('Content-Type: application/json');
    echo json_encode($response);
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
                    "sessionStartTime" => $response['session_starttime'],
                    "sessionRunning" => $response['session_running'],
                    "sessionProducts" => $response['session_products'],
                    "sessionFoundProducts" => $response['session_found'],
                    "sessionHints" => $response['session_hints']
                );
                header('Content-Type: application/json');
                echo json_encode($json);
            } else {
                http_response_code(400);
                echo "Session has expired";
                exit();
            }
        } catch (Exception $e) {
            // catch errors and return
            http_response_code(400);
            echo "No session with this sessionID";
            exit();
        }
    } else {
        // return error when no sessionID token is present
        http_response_code(400);
        echo "No sessionID token";
        exit();
    }
}