<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';
require 'Functions/addpoints.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['reservationID']='5';

//Parse POST Variables
if (isset($_POST['reservationID']) && !empty($_POST['reservationID'])) {
    $reservationID = $_POST['reservationID'];

	//begin transaction
	$mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);


    //Check if email matches a record in database and return customerID
    $checkedOutDate=date("Y-m-d H:i:s");
    $query = "	UPDATE Occupancy
				SET Occupancy.CheckOut=?
				WHERE Occupancy.ReservationID=?";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('si', $checkedOutDate, $reservationID);
    $stmt->execute();

    if ($mysqli->affected_rows==1) {
        //Find how many days customer has stayed in hotel
		$query = "SELECT DATEDIFF(Occupancy.CheckOut,Occupancy.CheckIn) + 1
					FROM Occupancy
					WHERE Occupancy.ReservationID = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $reservationID);
        $stmt->execute();
        $stmt->bind_result($days);
        $stmt->store_result();
        $stmt->fetch();
		$numrows = $stmt->num_rows;
		
		if($numrows==1){
			//add points to customer for each day he spent to hotel
			if (addPointsByReservationID($mysqli, $reservationID, "night", $days)) {
				$mysqli->commit();
				$jObj->success=1;
				$jObj->checkedOutDate=$checkedOutDate;
			} else {
				//Could not set his points
				$mysqli->rollback();
				$jObj->success=0;
				$jObj->errorMessage="Cannot set loyalty points";
			}				
		}
		else{
			//Could not find how many days customer stayed at hotel
			$mysqli->rollback();
			$jObj->success=0;
			$jObj->errorMessage="Error finding customer's occupancy";
		}
    } else {
		//Error checking out customer
		$mysqli->rollback();
        $jObj->success=0;
        $jObj->errorMessage="There is some problem with checkout procedure";
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