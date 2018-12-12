<?php

//DEBUG
/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

//Database connection variables
require 'dbConfig.php';
require 'Functions/RandomString.php';

//Create new database object
$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

//Response Object
$jObj = new stdClass();

//DEBUG
$_POST['customerID'] = 23;
$_POST['offerID'] = 2;

if (isset($_POST['customerID'],$_POST['offerID'])) {
    $customerID = $_POST['customerID'];
    $offerID = $_POST['offerID'];

    //check if customer is eligible for getting a coupon for this offer
    $query = "SELECT ee.ID
            FROM   (SELECT oe.ID
                    FROM   OfferExclusive oe, OfferExclusiveTier oet
                    WHERE  oe.ID = oet.OfferExclusiveID AND oet.TierID = getTierIDByCustomerID(?)
                    UNION
                    SELECT MinFreq.ID
                    FROM   (SELECT oe.ID, s.ServiceCategoryID, scf.MinimumUsages
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
                                 WHERE  oc.OfferExclusiveID = oe.ID
                                        AND oc.CustomerID =?
                                 GROUP  BY oe.ID, oe.MaximumUsage
                                 HAVING COUNT(oe.ID) >= oe.MaximumUsage
                                 UNION
                                 SELECT oe.ID
                                 FROM   OfferCoupon oc, OfferExclusive oe
                                 WHERE  oc.OfferExclusiveID = oe.ID
                                        AND oc.CustomerID =?
                                        AND Used = 0
                                        AND oc.Created >= CURRENT_DATE - INTERVAL 7 DAY)
                   AND ee.ID =?
            GROUP  BY ee.ID";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iiiii', $customerID, $customerID, $customerID, $customerID, $offerID);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->store_result();

    //if a row is found customer is eligible
    if ($stmt->num_rows == 1) {
        //should you generate code?
        $generateNewCode = true;
        //how many times we tried to generate code
        $timesGenerated = 0;
        //if we need to generate code and we haven't tried at least 10 times
        while ($generateNewCode && $timesGenerated<10) {
            //generate code
            $couponCode = random_str(2, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ').random_str(6, '0123456789');
            $codeCreated = date('Y-m-d');

            //try to insert coupon into database
            $query="INSERT INTO OfferCoupon (CustomerID, OfferExclusiveID, Code, Created)
                    SELECT ?, oe.ID, ?, ?
                    FROM   OfferExclusive oe
                           LEFT JOIN OfferCoupon oc
                                  ON oe.ID = oc.OfferExclusiveID
                    WHERE  oe.ID =?
                           AND ? NOT IN (SELECT oc.Code
                                         FROM   OfferCoupon oc, OfferExclusive oe
                                         WHERE  ( oc.Used = 0 )
                                                AND oc.OfferExclusiveID = oe.ID
                                                AND IFNULL(oe.EndDate, CURRENT_DATE) >= CURRENT_DATE
                                                AND oc.Created > CURRENT_DATE - INTERVAL 7 DAY)
                    LIMIT  1";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('issis', $customerID, $couponCode, $codeCreated, $offerID, $couponCode);
            $stmt->execute();
            $stmt->store_result();
            //if insert was successful we don't need to try again
            if ($stmt->affected_rows==1) {
                $generateNewCode = false;
            }
            //increase the try counter
            $timesGenerated++;
        }

        //Close Connection to DB
        $stmt->close();
        $mysqli->close();

        if(!$generateNewCode){
            //Build the json response
            $jObj->success = 1;
            $jObj->code = $couponCode;
            $jObj->codeCreated = $codeCreated;
        }
        else{
            $jObj->success = 0;
            $jObj->errorMessage="Could not create coupon. Please try again";
        }
    } else {
        $jObj->success = 0;
        $jObj->errorMessage="Customer is not qualified for getting a coupon for this offer";
    }
}
//Bad request
else {
    $jObj->success = 0;
    $jObj->errorMessage = "Bad request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
