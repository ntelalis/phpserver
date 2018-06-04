<?php


include 'dbConfig.php';
require 'Functions/externalpayment.php';
require 'Functions/addpoints.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);

//Response Object
$jObj = new stdClass();

if (isset($_POST['customerID'],$_POST['roomTypeID'],$_POST['arrival'],$_POST['departure'],$_POST['persons'],$_POST['freeNights'],$_POST['cashNights'],$_POST['ccNumber'],$_POST['ccName'],$_POST['ccMonth'],$_POST['ccYear'],$_POST['ccCVV'])) {
  $customerID = $_POST['customerID'];
  $roomTypeID = $_POST['roomTypeID'];
  $arrival = $_POST['arrival'];
  $departure = $_POST['departure'];
  $persons = $_POST['persons'];
  $freeNights = $_POST['freeNights'];
  $cashNights = $_POST['cashNights'];
  $ccNumber = $_POST['ccNumber'];
  $ccName = $_POST['ccName'];
  $ccMonth = $_POST['ccMonth'];
  $ccYear = $_POST['ccYear'];
  $ccCVV = $_POST['ccCVV'];

  //IMPLEMENT CURRENCY
  $currencyID = 1;

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
    $pointsNeeded += getFreeNightsPoints($dbCon,$roomTypeID,$persons)*$freeNights;
    $pointsNeeded += getCashNightsPoints($dbCon,$roomTypeID,$persons,$currencyID)*$cashNights;


    if($customerPoints>=$pointsNeeded){
      //points ok

      //Get total price
      $query = "SELECT (datediff(?,?)-?)*rtc.Price + ?*(rtpc.Cash-rtc.Price)
      from RoomType rt, RoomTypeCash rtc, RoomTypePointsAndCash rtpc
      where rt.ID=? and rt.ID=rtc.RoomTypeID And rtc.Persons=? and rtc.CurrencyID=?
      AND rtpc.RoomTypeID=rtc.RoomTypeID AND rtpc.Persons=rtc.Persons and rtpc.CurrencyID=rtc.CurrencyID";
      //$query = "SELECT (datediff(?,?)-?)*rt.Price - ?*? from RoomType rt where rt.ID=?";
      $stmt = $dbCon->prepare($query);
      $stmt->bind_param('ssiiiii', $departure, $arrival,$freeNights,$cashNights,$roomTypeID,$persons,$currencyID);
      $stmt->execute();
      $stmt->bind_result($totalPrice);
      $stmt->store_result();
      $stmt->fetch();

      //WARNING Must implement transaction Insert first then payment and commit or rollback!!!

      //Execute payment
      $paymentExecuted = externalPayment($ccNumber, $ccName, $ccMonth, $ccYear, $ccCVV, $totalPrice);

      //If payment was executed
      if ($paymentExecuted) {
        $bookDate = date('Y-m-d');
        $query = "INSERT INTO Reservation(CustomerID,RoomTypeID,ReservationTypeID,Adults,DateBooked,StartDate,EndDate) VALUES (?,?,3,?,?,?,?)";
        $stmt = $dbCon->prepare($query);
        $stmt->bind_param('iiisss', $customerID, $roomTypeID, $persons, $bookDate, $arrival, $departure);
        $success = $stmt->execute();

        $reservationId = $dbCon->insert_id;
        if ($success) {
          $jObj->success=1;
          $jObj->reservationID=$reservationId;
          $jObj->bookedDate = $bookDate;
        } else {
          $jObj->success=0;
          $jObj->errorMessage=$dbCon->error;
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
} else {
  $jObj->success=0;
  $jObj->ErrorMessage="There is a problem with the given parameters";
}

$stmt->close();
$dbCon->close();

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;
