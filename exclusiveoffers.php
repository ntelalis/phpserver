<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['customerID'] = 23;
//$_POST['check'] = '[{"id":2,"modified":"2018-10-25 09:44:31"}]';

if (isset($_POST['customerID'])) {

  $customerID = $_POST['customerID'];

  //Get all not expired exclusive offers for this customer both by his tier and
  //his service usage history and any active coupon if he has per offer
  $query = "SELECT oe.ID, o.ServiceID, o.Price, o.Discount, o.Title, o.Description, o.Details, oe.Special,
                   oe.StartDate, oe.EndDate, oc.Code, oc.Used, oc.Created, GREATEST(o.Modified, oe.Modified, IFNULL(oc.Modified, 0)) AS Modified
            FROM   (SELECT ee.ID
                    FROM   (SELECT oe.ID, oe.MaximumUsage
                            FROM   OfferExclusive oe, OfferExclusiveTier oet
                            WHERE  oe.ID = oet.OfferExclusiveID
                                   AND oet.TierID = getTierIDByCustomerID(?)
                            UNION
                            SELECT MinFreq.ID, MinFreq.MaximumUsage
                            FROM  (SELECT oe.ID, oe.MaximumUsage, s.ServiceCategoryID, scf.MinimumUsages
                                   FROM   Offer o, OfferExclusive oe, OfferExclusiveFrequency oef, Service s, ServiceCategoryFrequency scf
                                   WHERE  oef.OfferExclusiveID = oe.ID AND o.ID = oe.OfferID AND o.ServiceID = s.ID
                                          AND s.ServiceCategoryID = scf.CategoryID AND oef.FrequencyID = scf.FrequencyID) MinFreq,
                                  (SELECT s.ServiceCategoryID, COUNT(s.ServiceCategoryID) AS CustomerCount
                                   FROM   Charge ch, Reservation r, Service s
                                   WHERE  ch.ReservationID = r.ID AND ch.ServiceID = s.ID AND r.CustomerID = ?
                                   GROUP  BY s.ServiceCategoryID) CusFreq
                            WHERE  MinFreq.ServiceCategoryID = CusFreq.ServiceCategoryID AND MinFreq.MinimumUsages <= CusFreq.CustomerCount) ee
                    WHERE  ee.ID NOT IN (SELECT oe.ID
                                         FROM   OfferCoupon oc, OfferExclusive oe
                                         WHERE  oc.OfferExclusiveID = oe.ID AND oc.CustomerID =?
                                                AND ( ( oc.Created < CURRENT_DATE - INTERVAL 7 DAY AND oc.used = 0 ) OR ( oc.Used = 1 ) )
                                         GROUP  BY oe.ID, oe.MaximumUsage
                                         HAVING COUNT(oe.ID) >= oe.MaximumUsage)
                    GROUP  BY ee.ID) oo
                   LEFT JOIN OfferCoupon oc
                          ON ( oc.OfferExclusiveID = oo.ID
                               AND oc.CustomerID =?
                               AND oc.Used = 0
                               AND oc.Created > CURRENT_DATE - INTERVAL 7 DAY ), Offer o, OfferExclusive oe
            WHERE  o.ID = oe.OfferID
                   AND oo.ID = oe.ID
                   AND IFNULL(oe.EndDate, CURRENT_DATE) >= CURRENT_DATE";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('iiii',$customerID, $customerID, $customerID, $customerID);
  $stmt->execute();
  $stmt->bind_result($id, $serviceID, $price, $discount, $title, $description, $details, $special, $startDate, $endDate, $code, $codeUsed, $codeCreated, $modified);
  $stmt->store_result();

  //Check if customer has updated data for his reservations

  //Check if customer has any data for checking
  if (isset($_POST['check']) && !empty($_POST['check'])) {
      //parse json to array
      $jsonToCheck = json_decode($_POST['check']);
      //initialize a hash array which will be filled with rows client knows about
      $values = array();
      //for each row customer has
      foreach ($jsonToCheck as $item) {
          //get the id and the modified date of the row client knows
          $idClient = $item->id;
          $modifiedClient = $item->modified;
          //add these data to the array in order to be checked
          $values[$idClient]=$modifiedClient;
      }
  }

  //initialize the array which will be sent to client
  $exclusiveOfferArray = array();

  //fetch server data row by row
  while ($stmt->fetch()) {

  //check if client knows about this row by checking if this id
      //is found in array which was filled with client data
      //
      if (isset($values[$id])) {
          //convert client's and server's timestamps to time
          $timeInDB = strtotime($modified);
          $timeInClient = strtotime($values[$id]);
          //remove this id from the client's array because it was found and compared
          unset($values[$id]);
          //if client has latest data skip the row and continue to next one
          if ($timeInDB==$timeInClient) {
              continue;
          }
      }

      //if client doesnt know about this row or he hasn't the latest data
      //add the row to the response array

      $exclusiveOffer = new stdClass();
      $exclusiveOffer->id = $id;
      $exclusiveOffer->serviceID = $serviceID;
      if($price!=null){
        $exclusiveOffer->priceDiscount = $price."€";
      }
      else{
        $exclusiveOffer->priceDiscount = ($discount*100)."%";
      }
      $exclusiveOffer->title = $title;
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

  //for each row that was sent by the client and server didn't find
  //a match with his query to database
  foreach ($values as $key => $value) {
      //add it to response array but only set modified date with null value
      //so the client will delete it from his list
      $exclusiveOffer = new stdClass();
      $exclusiveOffer->id = $key;
      $exclusiveOffer->modified = null;
      $exclusiveOfferArray[]=$exclusiveOffer;
  }

  //Close Connection to DB
  $stmt->close();
  $mysqli->close();

  //Build the json response
  $jObj->success = 1;
  $jObj->exclusiveOfferArray = $exclusiveOfferArray;
}
//Bad request
else{
    $jObj->success = 0;
    $jObj->errorMessage = "Bad request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
