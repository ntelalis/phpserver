<?php
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
include 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

// $_POST['customerID'] = 23;
//$_POST['check'] = '[{"id":2,"modified":"2020-01-18 01:00:58"}]';

if (isset($_POST['customerID'])) {

  $customerID = $_POST['customerID'];

  $query = "SELECT oe.ID, o.ServiceID, o.Price, o.Discount, o.Description, o.Details,oe.Special, oe.StartDate, oe.EndDate, oc.Code, oc.Used, oc.Created, GREATEST(o.Modified, oe.Modified, IFNULL(oc.Modified, 0)) AS Modified
FROM (SELECT ee.ID
      FROM (SELECT oe.ID, oe.MaximumUsage
            FROM OfferExclusive oe
            WHERE oe.ID IN(SELECT t.ExclusiveOfferID
                              FROM OfferExclusiveTier t
                              WHERE t.TierID = getTierIDByCustomerID(?))
            UNION
            SELECT MinFreq.ID, MinFreq.MaximumUsage
            FROM(SELECT oe.ID, oe.MaximumUsage, hs.CategoryID, hscf.MinimumUsages
                 FROM Offer o, OfferExclusive oe, OfferExclusiveFrequency oef, HotelService hs, HotelServiceCategoryFrequency hscf
                 WHERE oef.ExclusiveOfferID = oe.ID AND o.ID = oe.OfferID AND o.ServiceID = hs.ID AND
                 hs.CategoryID = hscf.CategoryID AND oef.FrequencyID = hscf.FrequencyID) MinFreq,
            (SELECT hs.CategoryID, COUNT(hs.CategoryID) AS CustomerCount
             FROM Charge ch, Reservation r, HotelService hs
             WHERE ch.ReservationID = r.ID AND ch.HotelServiceID = hs.ID AND r.CustomerID = ?
             GROUP BY hs.CategoryID) CusFreq
            WHERE MinFreq.CategoryID = CusFreq.CategoryID AND MinFreq.MinimumUsages <= CusFreq.CustomerCount) ee LEFT JOIN
              OfferCoupon oc ON oc.ExclusiveOfferID=ee.ID
            WHERE ee.ID NOT IN (SELECT oe.ID
FROM OfferCoupon oc, OfferExclusive oe
WHERE oc.ExclusiveOfferID=oe.ID AND oc.CustomerID=? AND ((oc.Created<CURRENT_DATE-INTERVAL 7 DAY AND oc.used=0 )OR(oc.Used=1))
GROUP BY oe.ID,oe.MaximumUsage
HAVING COUNT(oe.ID)>=oe.MaximumUsage)
GROUP BY ee.ID) oo LEFT JOIN  OfferCoupon oc ON (oc.ExclusiveOfferID=oo.ID AND oc.CustomerID=? AND oc.Used=0 AND oc.Created>CURRENT_DATE-INTERVAL 7 DAY), Offer o, OfferExclusive oe
WHERE o.ID=oe.OfferID AND oo.ID=oe.ID AND IFNULL(oe.EndDate,CURRENT_DATE)>=CURRENT_DATE";

  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('iiii',$customerID, $customerID, $customerID, $customerID);
  $stmt->execute();
  $stmt->bind_result($id, $serviceID, $price, $discount, $description, $details, $special, $startDate, $endDate, $code, $codeUsed, $codeCreated, $modified);
  $stmt->store_result();

  if (isset($_POST['check']) && !empty($_POST['check'])) {
      $jsonToCheck = json_decode($_POST['check']);
      $values = array();
      foreach ($jsonToCheck as $item) {
          $idClient = $item->id;
          $modifiedClient = $item->modified;
          $values[$idClient]=$modifiedClient;
      }
  }

  $exclusiveOfferArray = array();
  while ($stmt->fetch()) {
      if (isset($values[$id])) {
          $timeInDB = strtotime($modified);
          $timeInClient = strtotime($values[$id]);
          unset($values[$id]);
          if (!($timeInDB>$timeInClient)) {
              continue;
          }
      }

      $exclusiveOffer = new stdClass();
      $exclusiveOffer->id = $id;
      $exclusiveOffer->serviceID = $serviceID;
      if($price!=null){
        $exclusiveOffer->priceDiscount = $price."€";
      }
      else{
        $exclusiveOffer->priceDiscount = ($discount*100)."%";
      }
      $exclusiveOffer->description = $description;
      $exclusiveOffer->details = $details;
      $exclusiveOffer->special = $special == 1;
      $exclusiveOffer->startDate = $startDate;
      $exclusiveOffer->endDate = $endDate;
      $exclusiveOffer->code = $code;
      $exclusiveOffer->codeUsed = $codeUsed == 1;
      $exclusiveOffer->codeCreated = $codeCreated;
      $exclusiveOffer->modified = $modified;
      $exclusiveOfferArray[] = $exclusiveOffer;
  }

  foreach ($values as $key => $value) {
      $exclusiveOffer = new stdClass();
      $exclusiveOffer->id = $key;
      $exclusiveOffer->modified = null;
      $exclusiveOfferArray[]=$exclusiveOffer;
  }

  $stmt->close();
  $mysqli->close();

  $jObj = new stdClass();
  $jObj->success = 1;
  $jObj->exclusiveOfferArray = $exclusiveOfferArray;
}
else{
  $jObj->success = 0;
  $jObj->errorMessage = "Bad request";
}

$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
