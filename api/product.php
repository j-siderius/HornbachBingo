<?php

include('db.php');
include('timer.php');
include('error.php');

if (isset($_GET['id'])) {
    getProduct();
} elseif (isset($_GET['checkproduct']) && isset($_GET['ean'])) {
    checkProduct();
} elseif (isset($_GET['hintproduct'])) {
    hintProduct();
} else {
    sendErrorMessage(404, "Endpoint not found");
    exit();
}

function getProduct()
{
    $id = htmlspecialchars($_GET['id']);

    try {
        global $conn;
        $query = $conn->prepare("SELECT `id`, `product_name`, `product_image` FROM `products` WHERE id=:id");
        $query->execute([
            'id' => $id
        ]);
        $response = $query->fetch(PDO::FETCH_ASSOC);

        $json = array(
            "productID" => $response['id'],
            "productName" => $response['product_name'],
            "productPicture" => $response['product_image']
        );
        header('Content-Type: application/json');
        echo json_encode($json);
        exit();
    } catch (Exception $e) {
        sendErrorMessage(400, "No valid productID");
        exit();
    }
}

function checkProduct()
{
    // mitigate brute-force by time-out of 250ms (crude implementation)
    usleep(250000);

    // check if session token is present
    if (isset($_COOKIE['sessionID'])) {
        $id = htmlspecialchars($_COOKIE['sessionID']);
        // check if the session is still valid
        if (checkGameTime($id)) {
            // get variables
            $productId = htmlspecialchars($_GET['checkproduct']);
            $productEan = htmlspecialchars($_GET['ean']);

            // fetch the session
            try {
                // get products in session array
                global $conn;
                $query = $conn->prepare("SELECT `session_products`, `session_found` FROM `sessions` WHERE `session_id`=:sesid");
                $query->execute([
                    "sesid" => $id
                ]);
                $response = $query->fetch(PDO::FETCH_ASSOC);
                $products = explode(",", $response['session_products']);
                $foundProducts = explode(",", $response['session_found']);

                // check if passed product is in session array
                if (!in_array($productId, $products)) {
                    sendErrorMessage(400, "Product not in session set");
                    exit();
                }

                //check if passed product not already found
                if (in_array($productId, $foundProducts)) {
                    sendErrorMessage(400, "Product already found");
                    exit();
                }

                // get the product EAN code
                $query = $conn->prepare("SELECT `product_ean` FROM `products` WHERE `id`=:prodid");
                $query->execute([
                    "prodid" => $productId
                ]);
                $reponse = $query->fetch(PDO::FETCH_ASSOC);
                $checkEan = $reponse['product_ean'];

                // check if passed EAN corresponds to actual EAN
                // TODO: change to check last xxxx numbers of EAN instead of whole
                if ($productEan != $checkEan) {
                    sendErrorMessage(400, "Product EAN incorrect");
                    exit();
                }

                // format products string
                $fprodstr = "";
                if (!$foundProducts[0] == "") {
                    foreach ($foundProducts as $found) {
                        $fprodstr .= $found . ",";
                    }
                }
                $fprodstr .= $productId;

                // set the product to found
                $query = $conn->prepare("UPDATE `sessions` SET `session_found`=:fprod WHERE `session_id`=:sesid");
                $query->execute([
                    "sesid" => $id,
                    "fprod" => $fprodstr
                ]);

                // respond with json
                $json = array(
                    "sessionID" => $id,
                    "foundProducts" => $fprodstr
                );
                header('Content-Type: application/json');
                echo json_encode($json);
                exit();
            } catch (Exception $e) {
                sendErrorMessage(400, "Invalid sessionID token");
                exit();
            }
        } else {
            sendErrorMessage(400, "Session has expired");
            exit();
        }
    } else {
        // return error when no sessionID token is present
        sendErrorMessage(400, "No sessionID token");
        exit();
    }
}

function hintProduct()
{
    // check if session token is present
    if (isset($_COOKIE['sessionID'])) {
        $id = htmlspecialchars($_COOKIE['sessionID']);
        // check if the session is still valid
        if (checkGameTime($id)) {

            try {
                // get the session hints
                global $conn;
                $query = $conn->prepare("SELECT `session_hints`, `session_products` FROM `sessions` WHERE `session_id`=:sesid");
                $query->execute([
                    "sesid" => $id
                ]);
                $response = $query->fetch(PDO::FETCH_ASSOC);
                $hints = explode(",", $response['session_hints']);
                $products = explode(",", $response['session_products']);

                // get variables
                $productId = htmlspecialchars($_GET['hintproduct']);

                // get the product location
                $query = $conn->prepare("SELECT `product_location` FROM `products` WHERE `id`=:prodid");
                $query->execute([
                    "prodid" => $productId
                ]);
                $reponse = $query->fetch(PDO::FETCH_ASSOC);
                $prodLocation = $reponse['product_location'];

                // check if product is in session set
                if (!in_array($productId, $products)) {
                    sendErrorMessage(400, "Product not in session set");
                    exit();
                }

                // check if queried product is already unlocked
                if (!in_array($productId, $hints)) {
                    // format hints string
                    $hprodstr = "";
                    if (!$hints[0] == "") {
                        foreach ($hints as $hint) {
                            $hprodstr .= $hint . ",";
                        }
                    }
                    $hprodstr .= $productId;

                    // set the product to found
                    $query = $conn->prepare("UPDATE `sessions` SET `session_hints`=:hprod WHERE `session_id`=:sesid");
                    $query->execute([
                        "sesid" => $id,
                        "hprod" => $hprodstr
                    ]);
                }

                // respond with json
                $json = array(
                    "sessionID" => $id,
                    "productID" => $productId,
                    "productLocation" => $prodLocation
                );
                header('Content-Type: application/json');
                echo json_encode($json);
                exit();
            } catch (Exception $e) {
                sendErrorMessage(400, "Invalid sessionID token");
                exit();
            }
        } else {
            sendErrorMessage(400, "Session has expired");
            exit();
        }
    } else {
        // return error when no sessionID token is present
        sendErrorMessage(400, "No sessionID token");
        exit();
    }
}
