<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['reservationID'] = 38;
//$_POST['order'] = '[{"id":1,"quantity":2},{"id":2,"quantity":1},{"id":5,"quantity":3}]';
//$_POST['comments'] = "Make it a good order";

//Parse POST Variables
if ($_POST['reservationID'] && isset($_POST['order'])) {
    $reservationId = $_POST['reservationID'];
    $order = $_POST['order'];
    $comments = $_POST['comments'];
    $json = json_decode($order);

    //begin transaction
    $mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

    //insert the order in the db
    $query = "INSERT INTO RoomServiceOrder(ReservationID,OrderDate,Comment) VALUES(?,NOW(),?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('is', $reservationId, $comments);
    $success = $stmt->execute();
    if ($success) {

        //get order id
        $orderid = $mysqli->insert_id;

        //insert all order details
        $query = "INSERT INTO RoomServiceOrderItem(RoomServiceOrderID,FoodID,Quantity) VALUES(?,?,?)";
        $stmt = $mysqli->prepare($query);
        $successful = true;
        foreach ($json as $item) {
            $stmt->bind_param('iii', $orderid, $item->id, $item->quantity);
            $success = $stmt->execute();
            if (!$success) {
                $successful = false;
                break;
            }
        }
        if ($successful) {

            //get total price for the order
            $query = "SELECT SUM(Quantity*Price)
                      FROM RoomServiceOrderItem
                        INNER JOIN Food f ON FoodID=ID
                      WHERE RoomServiceOrderID=?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('i', $orderid);
            $success = $stmt->execute();
            $stmt->bind_result($totalPrice);
            $stmt->store_result();
            $numrows = $stmt->num_rows;

            if ($numrows == 1) {
                $stmt->fetch();

                $query = "INSERT INTO Charge(ReservationID,ServiceID,Price)
                          SELECT ?,ID,? FROM Service WHERE Tag='roomService'";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('id', $reservationId, $totalPrice);
                $success = $stmt->execute();

                if ($success) {
                    $mysqli->commit();
                    $jObj->success=1;
                } else {
                    $mysqli->rollback();
                    $jObj->success=0;
                    $jObj->errorMessage=$stmt->error;
                }
            } else {
                $mysqli->rollback();
                $jObj->success=0;
                $jObj->errorMessage=$stmt->error;
            }
        } else {
            $mysqli->rollback();
            $jObj->success=0;
            $jObj->errorMessage=$stmt->error;
        }
    } else {
        $mysqli->rollback();
        $jObj->success=0;
        $jObj->errorMessage=$stmt->error;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();
}
//Bad request
else {
    $jObj->success = 0;
    $jObj->errorMessage = "Bad Request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
