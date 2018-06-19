<?php
/*
ini_set('display_errors',1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/
include 'dbConfig.php';
require 'Functions/externalpayment.php';
require 'Functions/addpoints.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

/*
$_POST['customerID']='23';
$_POST['roomTypeID']='2';
$_POST['arrival']='2018-01-02';
$_POST['departure']='2018-01-03';
$_POST['adults']='1';
$_POST['children']='0';
$_POST['freeNights']='0';
$_POST['cashNights']='0';
$_POST['ccNumber']='0';
$_POST['ccName']='0';
$_POST['ccYear']='0';
$_POST['ccMonth']='0';
$_POST['ccCVV']='0';
*/

if (isset($_POST['customerID'],$_POST['roomTypeID'],$_POST['arrival'],$_POST['departure'],$_POST['adults'],$_POST['children'],$_POST['freeNights'],$_POST['cashNights'],$_POST['ccNumber'],$_POST['ccName'],$_POST['ccMonth'],$_POST['ccYear'],$_POST['ccCVV'])) {
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

  //IMPLEMENT CURRENCY
  $currencyID = 1;

  //TODO remove points

$arrivalDate = new DateTime($arrival);
$departureDate = new DateTime($departure);
$dateDiff = $departureDate->diff($arrivalDate)->format("%a");

if($dateDiff>=$freeNights+$cashNights){
  //Checks if customer already has a reservation within given dates
  $query = "SELECT ID FROM Reservation WHERE CustomerID=? AND NOT (StartDate>=? OR EndDate<=?)";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('iss', $customerID, $departure, $arrival);
  $stmt->execute();
  $stmt->bind_result($resId);
  $stmt->store_result();
  $stmt->fetch();
  $numrows = $stmt->num_rows;

  //No reservation within dates found
  if ($numrows==0) {

    //check if enough points
    $customerPoints = getPointsByCustomerID($dbCon,$customerID);
    $pointsNeeded = 0;
    $pointsNeeded += getFreeNightsPoints($dbCon,$roomTypeID,$adults,$children)*$freeNights;
    $pointsNeeded += getCashNightsPoints($dbCon,$roomTypeID,$adults,$children,$currencyID)*$cashNights;


    if($customerPoints>=$pointsNeeded){
      //points ok

      //Get total price
      $query = "SELECT (datediff(?,?)-?)*rtc.Cash + ?*(rtcp.Cash-rtc.Cash)
      FROM RoomTypeCash rtc, RoomTypeCashPoints rtcp
      WHERE rtc.RoomTypeID=? AND rtc.Adults=? AND rtc.Children=? AND rtc.CurrencyID=?
      AND rtcp.RoomTypeID=rtc.RoomTypeID AND rtcp.Adults=rtc.Adults AND rtcp.Children=rtc.Children AND rtcp.CurrencyID=rtc.CurrencyID";
      //$query = "SELECT (datediff(?,?)-?)*rt.Price - ?*? from RoomType rt where rt.ID=?";
      $stmt = $dbCon->prepare($query);
      $stmt->bind_param('ssiiiiii', $departure, $arrival,$freeNights,$cashNights,$roomTypeID,$adults,$children,$currencyID);
      $stmt->execute();
      $stmt->bind_result($totalPrice);
      $stmt->store_result();
      $stmt->fetch();

      //WARNING Must implement transaction Insert first then payment and commit or rollback!!!

      //Execute payment
      $paymentExecuted = externalPayment($ccNumber, $ccName, $ccMonth, $ccYear, $ccCVV, $totalPrice);

      //If payment was executed
      if ($paymentExecuted) {

        if($freeNights>0){
          $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Points,DateSpent)
                    VALUES(?,(SELECT ID FROM LoyaltyPointsSpendingAction WHERE Name='Free Night'),
                          (SELECT SpendingPoints FROM RoomTypePoints WHERE RoomTypeID=? AND Adults=? AND Children=?)*?,
                          now())";
          $stmt = $dbCon->prepare($query);
          $stmt->bind_param('ii', $customerID, $roomTypeID, $adults, $children, $freeNights);
          $success = $stmt->execute();
        }
        if($cashNights>0){
          $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Points,DateSpent)
                    VALUES(?,(SELECT ID FROM LoyaltyPointsSpendingAction WHERE Name='Cash And Points'),
                          (SELECT Points FROM RoomTypeCashPoints WHERE RoomTypeID=? AND Adults=? AND Children=? AND CurrencyID=?)*?,
                          now())";
          $stmt = $dbCon->prepare($query);
          $stmt->bind_param('iiii', $customerID, $roomTypeID, $adults, $children, $currencyID, $cashNights);
          $success = $stmt->execute();
        }

        $bookDate = date('Y-m-d');
        $query = "INSERT INTO Reservation(CustomerID,RoomTypeID,ReservationTypeID,Adults,Children,DateBooked,StartDate,EndDate) VALUES (?,?,3,?,?,?,?,?)";
        $stmt = $dbCon->prepare($query);
        $stmt->bind_param('iiiisss', $customerID, $roomTypeID, $adults, $children, $bookDate, $arrival, $departure);
        $success = $stmt->execute();

        $reservationId = $dbCon->insert_id;
        if ($success) {
          $jObj->success=1;
          $jObj->reservationID=$reservationId;
          $jObj->bookedDate = $bookDate;
        } else {
          $jObj->success=0;
          $jObj->errorMessage="$dbCon->error";
        }
      } else {
        $jObj->success=0;
        $jObj->ErrorMessage="There is an error with the payment. Please try again later";
      }
    }
    else{
      $jObj->success=0;
      $jObj->errorMessage="Something is wrong with loyalty points!";
    }

  } else {
    $jObj->success=0;
    $jObj->errorMessage="You already have an active reservation within these days";
  }
  $stmt->close();
  $dbCon->close();
}
else{
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
