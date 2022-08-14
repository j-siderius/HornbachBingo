<?php

include('db.php');
include('timer.php');

if (isset($_GET['id'])) {
    getProduct();
} elseif (isset($_GET['checkproduct']) && isset($_GET['ean'])) {
    checkProduct();
} elseif (isset($_GET['hintproduct'])) {
    hintProduct();
} else {
    http_response_code(404);
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
        http_response_code(400);
        echo "No valid productID";
        exit();
    }
}

function checkProduct()
{
    // mitigate brute-force by time-out of 450ms (crude implementation)
    usleep(450000);

    // check if session token is present
    if (isset($_COOKIE['sessionID'])) {
        $id = htmlspecialchars($_COOKIE['sessionID']);
        // check if the session is still valid
        if (checkGameTime($id)) {
            // get variables
            $productId = htmlspecialchars($_GET['checkproduct']);
            $productEan = htmlspecialchars($_GET['ean']);

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
                http_response_code(400);
                echo "Product not in session set";
                exit();
            }

            //check if passed product not already found
            if (in_array($productId, $foundProducts)) {
                http_response_code(400);
                echo "Product already found";
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
                http_response_code(400);
                echo "Product EAN incorrect";
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
            $response = array(
                "sessionID" => $id,
                "foundProducts" => $fprodstr
            );
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            http_response_code(400);
            echo "Session has expired";
            exit();
        }
    } else {
        // return error when no sessionID token is present
        http_response_code(400);
        echo "No sessionID token";
        exit();
    }
}

// TODO: implement saving which hints have been used

function hintProduct()
{
    // check if session token is present
    if (isset($_COOKIE['sessionID'])) {
        $id = htmlspecialchars($_COOKIE['sessionID']);
        // check if the session is still valid
        if (checkGameTime($id)) {
            // get variables
            $productId = htmlspecialchars($_GET['checkproduct']);

            // get the product location
            global $conn;
            $query = $conn->prepare("SELECT `product_location` FROM `products` WHERE `id`=:prodid");
            $query->execute([
                "prodid" => $productId
            ]);
            $reponse = $query->fetch(PDO::FETCH_ASSOC);
            $prodLocation = $reponse['product_location'];

            // get the session hints
            $query = $conn->prepare("SELECT `session_hints` FROM `sessions` WHERE `session_id`=:sesid");
            $query->execute([
                "sesid" => $id
            ]);
            $response = $query->fetch(PDO::FETCH_ASSOC);
            $hints = $response['session_hints'];

            // increase the hint counter for the session
            $hints += 1;
            $query = $conn->prepare("UPDATE `sessions` SET `session_hints`=:hints WHERE `session_id`=:sesid");
            $query->execute([
                "sesid" => $id,
                "hints" => $hints
            ]);

            // respond with json
            $response = array(
                "sessionID" => $id,
                "hintsUsed" => $hints,
                "productID" => $productId,
                "productLocation" => $prodLocation
            );
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } else {
            http_response_code(400);
            echo "Session has expired";
            exit();
        }
    } else {
        // return error when no sessionID token is present
        http_response_code(400);
        echo "No sessionID token";
        exit();
    }
}
