<?php

/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

require 'Functions/RandomString.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

$_POST['customerID'] = 23;
$_POST['offerID'] = 3;
//$_POST['check'] = '[{"id":2,"modified":"2020-01-18 01:00:58"}]';

if (isset($_POST['customerID'],$_POST['offerID'])) {

  $customerID = $_POST['customerID'];
  $offerID = $_POST['offerID'];

  $query = "SELECT oe.ID
            FROM (SELECT oe.ID, oe.ServiceID, oe.Price, oe.Discount, oe.Description, oe.Special, oe.StartDate, oe.EndDate, oe.Modified
                 FROM OfferExclusive oe
                 WHERE oe.ID IN(SELECT t.ExclusiveOfferID
          		                 FROM OfferExclusiveTier t
          		                 WHERE t.TierID = getTierIDByCustomerID(?))
                 UNION
                 SELECT MinFreq.ID, MinFreq.ServiceID, MinFreq.Price, MinFreq.Discount, MinFreq.Description, MinFreq.Special, MinFreq.StartDate, MinFreq.EndDate, MinFreq.Modified
                 FROM(SELECT oe.*, hs.CategoryID, hscf.MinimumUsages
                      FROM OfferExclusiveFrequency oef, OfferExclusive oe, HotelService hs, HotelServiceCategoryFrequency hscf
                      WHERE oef.ExclusiveOfferID = oe.ID AND oe.ServiceID = hs.ID AND hs.CategoryID = hscf.CategoryID AND oef.FrequencyID = hscf.FrequencyID) MinFreq,
      	             (SELECT hs.CategoryID, COUNT(hs.CategoryID) AS CustomerCount
                      FROM Charge ch, Reservation r, HotelService hs
      	              WHERE ch.ReservationID = r.ID AND ch.HotelServiceID = hs.ID AND r.CustomerID = ?
                      GROUP BY hs.CategoryID) CusFreq
                 WHERE MinFreq.CategoryID = CusFreq.CategoryID AND MinFreq.MinimumUsages <= CusFreq.CustomerCount) oe
            WHERE oe.ID=?";

  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('iii',$customerID, $customerID, $offerID);
  $stmt->execute();
  $stmt->bind_result($id);
  $stmt->store_result();

	if ($stmt->num_rows == 1){
    $generateNewCode = true;
    while($generateNewCode){
      $couponCode = random_str(2,'ABCDEFGHIJKLMNOPQRSTUVWXYZ').random_str(6,'0123456789');
      $codeCreated = date('Y-m-d');

      $query="INSERT INTO OfferCoupon(CustomerID,ExclusiveOfferID,Code,Created)
              SELECT ?,?,?,?
              FROM OfferCoupon oc
              WHERE ? NOT IN (SELECT oc.Code
                              FROM OfferCoupon oc, OfferExclusive oe
                              WHERE oc.Used = 0 AND oc.ExclusiveOfferID = oe.ID AND
                                  IFNULL(oe.EndDate, CURRENT_DATE) >= CURRENT_DATE AND
                                  oc.Created >= CURRENT_DATE - INTERVAL 7 DAY)
              LIMIT 1;";

      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('iisss',$customerID, $offerID, $couponCode, $codeCreated, $couponCode);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows==1){
        $generateNewCode = false
      }
    }
    $stmt->close();
    $mysqli->close();
    $jObj = new stdClass();

    $jObj->success = 1;

    $jObj->code = $couponCode;
    $jObj->codeCreated = $codeCreated;
    $jObj->codeUsed = false;
  }
  else{
    $jObj->success = 0;
    $jObj->errorMessage="Customer is not qualified for this offer";
  }
}
else{
  $jObj->success = 0;
  $jObj->errorMessage="Bad request";
}
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
