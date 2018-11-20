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

//$_POST['customerID'] = 23;
//$_POST['offerID'] = 3;
//$_POST['check'] = '[{"id":2,"modified":"2020-01-18 01:00:58"}]';

if (isset($_POST['customerID'],$_POST['offerID'])) {

  $customerID = $_POST['customerID'];
  $offerID = $_POST['offerID'];

  $query = "SELECT ee.ID
    FROM (SELECT oe.ID, oe.MaximumUsage
          FROM OfferExclusive oe
          WHERE oe.ID IN(SELECT t.OfferExclusiveID
                            FROM OfferExclusiveTier t
                            WHERE t.TierID = getTierIDByCustomerID(?))
          UNION
          SELECT MinFreq.ID, MinFreq.MaximumUsage
          FROM(SELECT oe.ID, oe.MaximumUsage, hs.CategoryID, hscf.MinimumUsages
               FROM Offer o, OfferExclusive oe, OfferExclusiveFrequency oef, Service hs, ServiceCategoryFrequency hscf
               WHERE oef.OfferExclusiveID = oe.ID AND o.ID = oe.OfferID AND o.ServiceID = hs.ID AND
               hs.CategoryID = hscf.CategoryID AND oef.FrequencyID = hscf.FrequencyID) MinFreq,
          (SELECT hs.CategoryID, COUNT(hs.CategoryID) AS CustomerCount
           FROM Charge ch, Reservation r, Service hs
           WHERE ch.ReservationID = r.ID AND ch.ServiceID = hs.ID AND r.CustomerID = ?
           GROUP BY hs.CategoryID) CusFreq
          WHERE MinFreq.CategoryID = CusFreq.CategoryID AND MinFreq.MinimumUsages <= CusFreq.CustomerCount) ee LEFT JOIN
            OfferCoupon oc ON oc.OfferExclusiveID=ee.ID
          WHERE ee.ID NOT IN (SELECT oe.ID
FROM OfferCoupon oc, OfferExclusive oe
WHERE oc.OfferExclusiveID=oe.ID AND oc.CustomerID=?
GROUP BY oe.ID,oe.MaximumUsage
HAVING COUNT(oe.ID)>=oe.MaximumUsage
UNION
SELECT oe.ID
FROM OfferCoupon oc, OfferExclusive oe
WHERE oc.OfferExclusiveID=oe.ID AND oc.CustomerID=? AND Used=0 AND oc.Created>=CURRENT_DATE-INTERVAL 7 DAY) AND ee.ID=?
GROUP BY ee.ID";

  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('iiiii',$customerID, $customerID, $customerID, $customerID, $offerID);
  $stmt->execute();
  $stmt->bind_result($id);
  $stmt->store_result();

	if ($stmt->num_rows == 1){
    $generateNewCode = true;
    $timesGenerated = 0;
    while($generateNewCode && $timesGenerated<10 ){
      $couponCode = random_str(2,'ABCDEFGHIJKLMNOPQRSTUVWXYZ').random_str(6,'0123456789');
      $codeCreated = date('Y-m-d');

      $query="INSERT INTO OfferCoupon(CustomerID,OfferExclusiveID,Code,Created)
              SELECT ?,oe.ID,?,?
              FROM OfferExclusive oe LEFT JOIN OfferCoupon oc ON oe.ID=oc.OfferExclusiveID
              WHERE oe.ID=? AND ? NOT IN (SELECT oc.Code
                              FROM OfferCoupon oc, OfferExclusive oe
                              WHERE (oc.Used = 0) AND oc.OfferExclusiveID = oe.ID AND
                                  IFNULL(oe.EndDate, CURRENT_DATE) >= CURRENT_DATE AND
                                  oc.Created > CURRENT_DATE - INTERVAL 7 DAY)
              LIMIT 1;";

      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('issis',$customerID, $couponCode, $codeCreated, $offerID, $couponCode);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->affected_rows==1){
        $generateNewCode = false;
      }
      $timesGenerated++;
    }
    $stmt->close();
    $mysqli->close();
    $jObj = new stdClass();

    $jObj->success = 1;
    $jObj->offerID = $offerID;
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
