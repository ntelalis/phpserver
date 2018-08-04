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

if (isset($_POST['customerID'])) {

  $customerID = $_POST['customerID'];

  $query = "SELECT o.ID, o.ServiceID, o.Price, o.Discount, o.Description, o.Special, o.StartDate, o.EndDate, o.Modified
           FROM OfferExclusive o
           WHERE o.ID IN(SELECT t.ExclusiveOfferID
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
           WHERE MinFreq.CategoryID = CusFreq.CategoryID AND MinFreq.MinimumUsages <= CusFreq.CustomerCount";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('ii',$customerID,$customerID);
  $stmt->execute();
  $stmt->bind_result($id, $serviceID, $price, $discount, $description, $special, $startDate, $endDate, $modified);
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
      $exclusiveOffer->price = $price;
      $exclusiveOffer->discount = $discount;
      $exclusiveOffer->description = $description;
      $exclusiveOffer->special = $special == 1;
      $exclusiveOffer->startDate = $startDate;
      $exclusiveOffer->endDate = $endDate;
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
