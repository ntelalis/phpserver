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
//$_POST['offerID'] = 2;
//$_POST['check'] = '[{"id":2,"modified":"2020-01-18 01:00:58"}]';

if (isset($_POST['customerID'],$_POST['offerID'])) {

  $customerID = $_POST['customerID'];
  $offerID = $_POST['offerID'];

  $query = "SELECT ee.ID
            FROM (SELECT oe.ID
	                FROM OfferExclusive oe, OfferExclusiveTier oet
	                WHERE oe.ID=oet.OfferExclusiveID AND oet.TierID = getTierIDByCustomerID(?)
	                UNION
	                SELECT MinFreq.ID
	                FROM (SELECT oe.ID, s.ServiceCategoryID, scf.MinimumUsages
			                  FROM Offer o, OfferExclusive oe, OfferExclusiveFrequency oef, Service s, ServiceCategoryFrequency scf
			WHERE oef.OfferExclusiveID = oe.ID AND o.ID = oe.OfferID AND o.ServiceID = s.ID AND
				s.ServiceCategoryID = scf.CategoryID AND oef.FrequencyID = scf.FrequencyID) MinFreq,
		   (SELECT s.ServiceCategoryID, COUNT(s.ServiceCategoryID) AS CustomerCount
			FROM Charge ch, Reservation r, Service s
			WHERE ch.ReservationID = r.ID AND ch.ServiceID = s.ID AND r.CustomerID = ?
			GROUP BY s.ServiceCategoryID) CusFreq
	  WHERE MinFreq.ServiceCategoryID = CusFreq.ServiceCategoryID AND MinFreq.MinimumUsages <= CusFreq.CustomerCount) ee
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
