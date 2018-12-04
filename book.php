<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';
require 'Functions/externalpayment.php';
require 'Functions/addpoints.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
/*
$_POST['customerID']='23';
$_POST['roomTypeID']='1';
$_POST['arrival']='2019-11-28';
$_POST['departure']='2019-11-30';
$_POST['adults']='1';
$_POST['children']='0';
$_POST['freeNights']='2';
$_POST['cashNights']='0';
$_POST['ccNumber']='0';
$_POST['ccName']='0';
$_POST['ccYear']='0';
$_POST['ccMonth']='0';
$_POST['ccCVV']='888';
$_POST['phone']='6989665086';
$_POST['address1']='Mavromixali 52';
$_POST['address2']='';
$_POST['city']='salonica';
$_POST['postalCode']='2124';
*/

if (isset($_POST['customerID'],$_POST['roomTypeID'],$_POST['arrival'],$_POST['departure'],$_POST['adults'],
          $_POST['children'],$_POST['freeNights'],$_POST['cashNights'],$_POST['ccNumber'],$_POST['ccName'],
          $_POST['ccMonth'],$_POST['ccYear'],$_POST['ccCVV'],$_POST['phone'],$_POST['address1'],
          $_POST['address2'],$_POST['city'],$_POST['postalCode'])) {

    //reservation
    $customerID = $_POST['customerID'];
    $roomTypeID = $_POST['roomTypeID'];
    $arrival = $_POST['arrival'];
    $departure = $_POST['departure'];
    $adults = $_POST['adults'];
    $children = $_POST['children'];

    //loyalty program
    $freeNights = $_POST['freeNights'];
    $cashNights = $_POST['cashNights'];

    //card info
    $ccNumber = $_POST['ccNumber'];
    $ccName = $_POST['ccName'];
    $ccMonth = $_POST['ccMonth'];
    $ccYear = $_POST['ccYear'];
    $ccCVV = $_POST['ccCVV'];

    //contact info
    $phone = $_POST['phone'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $city = $_POST['city'];
    $postalCode = $_POST['postalCode'];

    //convert strings to datetime objects
    $arrivalDate = new DateTime($arrival);
    $departureDate = new DateTime($departure);

    //How many days is the reservation (%a switch is days)
    $dateDiff = $departureDate->diff($arrivalDate)->format("%a");

    //extra check (this is also implemented in client): check if selected days from reward program aren't greater
    //reservation days selected
    if ($dateDiff>=$freeNights+$cashNights) {
        //Checks if customer already has a reservation within given dates
        $query = "SELECT ID FROM Reservation WHERE CustomerID=? AND NOT (StartDate>=? OR EndDate<=?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('iss', $customerID, $departure, $arrival);
        $stmt->execute();
        $stmt->bind_result($resId);
        $stmt->store_result();
        $stmt->fetch();
        $numrows = $stmt->num_rows;
        $stmt->close();

        //TODO Check if the roomtype is available for these dates for extra check

        //All good. No reservation within dates found
        if ($numrows==0) {

            //get customer points
            $customerPoints = getPointsByCustomerID($mysqli, $customerID);

            //get how many points are needed for his selection
            $pointsNeeded = 0;
            $pointsNeeded += getFreeNightsPoints($mysqli, $roomTypeID, $adults, $children)*$freeNights;
            $pointsNeeded += getCashNightsPoints($mysqli, $roomTypeID, $adults, $children)*$cashNights;

            //extra check (also implemented in client) check if customer has enough points
            if ($customerPoints>=$pointsNeeded) {

                //flag and errorList for checking if any error occured
                $error = false;
                $errormsg = array();

                //Get total cash price for his choice
                $query = "  SELECT (datediff(?,?)-?)*rtc.Cash + ?*(rtcp.Cash-rtc.Cash)
                            FROM RoomTypeCash rtc, RoomTypeCashPoints rtcp
                            WHERE rtc.RoomTypeID=? AND rtc.Adults=? AND rtc.Children=?
                            AND rtcp.RoomTypeID=rtc.RoomTypeID AND rtcp.Adults=rtc.Adults
                            AND rtcp.Children=rtc.Children";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('ssiiiii', $departure, $arrival, $freeNights, $cashNights, $roomTypeID, $adults, $children);
                $success = $stmt->execute();
                $stmt->bind_result($totalPrice);
                $stmt->store_result();
                $stmt->fetch();

                if (!$success) {
                    //error inserting
                    $error = true;
                    $errormsg[] = array("table"=>"Total cash", "errMsg"=>$stmt->error);
                    echo $totalPrice;
                }


                //begin transaction
                $mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

                //if customer selected freeNights
                if ($freeNights>0) {
                    //Insert points needed for his choice in the spendpointshistory table
                    $query = "  INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Points,DateSpent)
                                VALUES(?,(SELECT ID FROM LoyaltyPointsSpendingAction WHERE Name='Free Night'),
                                (SELECT SpendingPoints FROM RoomTypePoints WHERE RoomTypeID=? AND Adults=? AND Children=?)*?,now())";
                    $stmt = $mysqli->prepare($query);
                    $stmt->bind_param('iiiii', $customerID, $roomTypeID, $adults, $children, $freeNights);
                    $success = $stmt->execute();

                    if (!$success) {
                        //error inserting
                        $error = true;
                        $errormsg[] = array("table"=>"free nights", "errMsg"=>$stmt->error);
                    }
                }
                //if customer selected cash and points nights
                if ($cashNights>0) {
                    //Insert points needed for his choice in the spendpointshistory table
                    $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Points,DateSpent)
                    VALUES(?,(SELECT ID FROM LoyaltyPointsSpendingAction WHERE Name='Cash And Points'),
                    (SELECT Points FROM RoomTypeCashPoints WHERE RoomTypeID=? AND Adults=? AND Children=?)*?,
                    now())";
                    $stmt = $mysqli->prepare($query);
                    $stmt->bind_param('iiiii', $customerID, $roomTypeID, $adults, $children, $cashNights);
                    $success = $stmt->execute();

                    if (!$success) {
                        //error inserting
                        $error = true;
                        $errormsg[] = array("table"=>"cash nights", "errMsg"=>$stmt->error);
                    }
                }

                //insert reservation into DB
                $bookDate = date('Y-m-d');
                $modified = date("Y-m-d H:i:s");
                $query = "INSERT INTO Reservation(CustomerID,RoomTypeID,ReservationTypeID,Adults,Children,DateBooked,StartDate,EndDate,Modified) VALUES (?,?,3,?,?,?,?,?,?)";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('iiiissss', $customerID, $roomTypeID, $adults, $children, $bookDate, $arrival, $departure, $modified);
                $success = $stmt->execute();

                if (!$success) {
                    //error inserting
                    $error = true;
                    $errormsg[] = array("table"=>"reservation", "errMsg"=>$stmt->error);
                }

                $reservationId = $mysqli->insert_id;

                //insert/update customer contact info
                $query = "INSERT INTO ContactInfo (CustomerID,Phone,Address1,Address2,City,PostalCode)
                VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY
                UPDATE Phone = ?, Address1 = ?, Address2 = ?, City = ?, PostalCode = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('issssssssss', $customerID, $phone, $address1, $address2, $city, $postalCode, $phone, $address1, $address2, $city, $postalCode);
                $success = $stmt->execute();

                if (!$success) {
                    //error inserting
                    $error = true;
                    $errormsg[] = array("table"=>"contact info", "errMsg"=>$stmt->error);
                }

                //insert payment into charge
                $query = "INSERT INTO Charge (ReservationID,ServiceID,PaymentMethodID,Price,TimePaid)
                SELECT ?,NULL,pm.ID,?,NOW()
                FROM PaymentMethod pm
                WHERE pm.Method='Card'";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('ii', $reservationId, $totalPrice);
                $success = $stmt->execute();

                if (!$success) {
                    //error inserting
                    $error = true;
                    $errormsg[] = array("table"=>"charge", "errMsg"=>$stmt->error);
                }

                if (!$error) {
                    //Execute payment
                    $paymentExecuted = externalPayment($ccNumber, $ccName, $ccMonth, $ccYear, $ccCVV, $totalPrice);

                    //If payment was executed
                    if ($paymentExecuted) {
                        $mysqli->commit();
                        $jObj->success=1;
                        $jObj->reservationID=$reservationId;
                        $jObj->bookedDate = $bookDate;
                        $jObj->modified = $modified;
                    } else {
                        //not successful payment
                        $mysqli->rollback();
                        $jObj->success=0;
                        $jObj->errorMessage="There is an error with the payment. Please try again later";
                    }
                } else {
                    //could not insert data into tables
                    $mysqli->rollback();
                    $jObj->success=0;
                    $jObj->errorMessage="Error inserting data";
                    $jObj->errorList = $errormsg;
                }
            } else {
                //not enough points
                $jObj->success=0;
                $jObj->errorMessage="Not enough points";
            }
        } else {
            //already active reservation
            $jObj->success=0;
            $jObj->errorMessage="You already have an active reservation within these days";
        }

        //Close Connection to DB
        $stmt->close();
        $mysqli->close();
    } else {
        //reward nights picked dates greater than reservation days
        $jObj->success=0;
        $jObj->errorMessage="Loyalty reward nights greater than picked dates";
    }
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
