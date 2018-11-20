<?php
/*
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

include 'dbConfig.php';
require 'Functions/externalpayment.php';
require 'Functions/addpoints.php';

$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

/*
$_POST['customerID']='23';
$_POST['roomTypeID']='2';
$_POST['arrival']='2018-06-26';
$_POST['departure']='2018-06-27';
$_POST['adults']='1';
$_POST['children']='0';
$_POST['freeNights']='0';
$_POST['cashNights']='1';
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

if (isset($_POST['customerID'],$_POST['roomTypeID'],$_POST['arrival'],$_POST['departure'],$_POST['adults'],$_POST['children'],$_POST['freeNights'],$_POST['cashNights'],$_POST['ccNumber'],$_POST['ccName'],$_POST['ccMonth'],$_POST['ccYear'],$_POST['ccCVV'],$_POST['phone'],$_POST['address1'],$_POST['address2'],$_POST['city'],$_POST['postalCode'])) {
    $customerID = $_POST['customerID'];
    $roomTypeID = $_POST['roomTypeID'];
    $arrival = $_POST['arrival'];
    $departure = $_POST['departure'];
    $adults = $_POST['adults'];
    $children = $_POST['children'];
    $freeNights = $_POST['freeNights'];
    $cashNights = $_POST['cashNights'];
    $ccNumber = $_POST['ccNumber'];
    $ccName = $_POST['ccName'];
    $ccMonth = $_POST['ccMonth'];
    $ccYear = $_POST['ccYear'];
    $ccCVV = $_POST['ccCVV'];

    $phone = $_POST['phone'];
    $address1 = $_POST['address1'];
    $address2 = $_POST['address2'];
    $city = $_POST['city'];
    $postalCode = $_POST['postalCode'];

    //IMPLEMENT CURRENCY
    $currencyID = 1;

    //TODO remove points

    $arrivalDate = new DateTime($arrival);
    $departureDate = new DateTime($departure);
    $dateDiff = $departureDate->diff($arrivalDate)->format("%a");

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

        //No reservation within dates found
        if ($numrows==0) {

    //check if enough points
            $customerPoints = getPointsByCustomerID($mysqli, $customerID);
            $pointsNeeded = 0;
            $pointsNeeded += getFreeNightsPoints($mysqli, $roomTypeID, $adults, $children)*$freeNights;
            $pointsNeeded += getCashNightsPoints($mysqli, $roomTypeID, $adults, $children, $currencyID)*$cashNights;


            if ($customerPoints>=$pointsNeeded) {
                //points ok

                //Get total price
                $query = "SELECT (datediff(?,?)-?)*rtc.Cash + ?*(rtcp.Cash-rtc.Cash)
      FROM RoomTypeCash rtc, RoomTypeCashPoints rtcp
      WHERE rtc.RoomTypeID=? AND rtc.Adults=? AND rtc.Children=? AND rtc.CurrencyID=?
      AND rtcp.RoomTypeID=rtc.RoomTypeID AND rtcp.Adults=rtc.Adults AND rtcp.Children=rtc.Children AND rtcp.CurrencyID=rtc.CurrencyID";
                //$query = "SELECT (datediff(?,?)-?)*rt.Price - ?*? from RoomType rt where rt.ID=?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('ssiiiiii', $departure, $arrival, $freeNights, $cashNights, $roomTypeID, $adults, $children, $currencyID);
                $stmt->execute();
                $stmt->bind_result($totalPrice);
                $stmt->store_result();
                $stmt->fetch();

                $mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

                if ($freeNights>0) {
                    $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Points,DateSpent)
                VALUES(?,(SELECT ID FROM LoyaltyPointsSpendingAction WHERE Name='Free Night'),
                      (SELECT SpendingPoints FROM RoomTypePoints WHERE RoomTypeID=? AND Adults=? AND Children=?)*?,
                      now())";
                    $stmt = $mysqli->prepare($query);
                    $stmt->bind_param('iiiii', $customerID, $roomTypeID, $adults, $children, $freeNights);
                    $success = $stmt->execute();
                    if(!$success){
                      exit(1);
                    }
                }
                if ($cashNights>0) {
                    $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Points,DateSpent)
                VALUES(?,(SELECT ID FROM LoyaltyPointsSpendingAction WHERE Name='Cash And Points'),
                      (SELECT Points FROM RoomTypeCashPoints WHERE RoomTypeID=? AND Adults=? AND Children=? AND CurrencyID=?)*?,
                      now())";
                    $stmt = $mysqli->prepare($query);
                    $stmt->bind_param('iiiiii', $customerID, $roomTypeID, $adults, $children, $currencyID, $cashNights);
                    $success = $stmt->execute();
                    if(!$success){
                      exit(1);
                    }
                }

                $bookDate = date('Y-m-d');
                $modified = date("Y-m-d H:i:s");
                $query = "INSERT INTO Reservation(CustomerID,RoomTypeID,ReservationTypeID,Adults,Children,DateBooked,StartDate,EndDate,Modified) VALUES (?,?,3,?,?,?,?,?,?)";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('iiiissss', $customerID, $roomTypeID, $adults, $children, $bookDate, $arrival, $departure, $modified);
                $success = $stmt->execute();

                $reservationId = $mysqli->insert_id;

                $query = "INSERT INTO ContactInfo (CustomerID,Phone,Address1,Address2,City,PostalCode)
                          VALUES (?,?,?,?,?,?)
                          ON DUPLICATE KEY
                          UPDATE Phone = ?, Address1 = ?, Address2 = ?, City = ?, PostalCode = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('issssssssss',$customerID, $phone, $address1, $address2, $city, $postalCode, $phone, $address1, $address2, $city, $postalCode);
                $success = $stmt->execute();

                $query = "INSERT INTO Charge (ReservationID,ServiceID,PaymentMethodID,Price,TimePaid)
                          SELECT ?,NULL,pm.ID,?,NOW()
                          FROM PaymentMethod pm
                          WHERE pm.Method='Card'";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('id',$reservationId, $totalPrice);
                $success = $stmt->execute();

                if ($success) {

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
                      $mysqli->rollback();
                      $jObj->success=0;
                      $jObj->errorMessage="There is an error with the payment. Please try again later";
                  }
                } else {
                    $mysqli->rollback();
                    $jObj->success=0;
                    $jObj->errorMessage=$mysqli->error;
                }


            } else {
                $jObj->success=0;
                $jObj->errorMessage="Something is wrong with loyalty points!";
            }
        } else {
            $jObj->success=0;
            $jObj->errorMessage="You already have an active reservation within these days";
        }
        $stmt->close();
        $mysqli->close();
    } else {
        $jObj->success=0;
        $jObj->errorMessage="Something is wrong with date picked!";
    }
} else {
    $jObj->success=0;
    $jObj->ErrorMessage="There is a problem with the given parameters";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
