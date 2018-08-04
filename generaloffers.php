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

//$_POST['check'] = '[{"id":1,"modified":"2115-01-18 01:00:58"}]';

$query = "SELECT ID, Price, Discount, Description, StartDate, EndDate, Modified
          FROM OfferGeneral";
$stmt = $mysqli->prepare($query);
$stmt->execute();
$stmt->bind_result($id, $price, $discount, $description, $startDate, $endDate, $modified);
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

$generalOfferArray = array();
while ($stmt->fetch()) {
    if (isset($values[$id])) {
        $timeInDB = strtotime($modified);
        $timeInClient = strtotime($values[$id]);
        unset($values[$id]);
        if (!($timeInDB>$timeInClient)) {
            continue;
        }
    }

    $generalOffer = new stdClass();
    $generalOffer->id = $id;
    $generalOffer->price = $price;
    $generalOffer->discount = $discount;
    $generalOffer->description = $description;
    $generalOffer->startDate = $startDate;
    $generalOffer->endDate = $endDate;
    $generalOffer->modified = $modified;
    $generalOfferArray[] = $generalOffer;
}

foreach ($values as $key => $value) {
    $generalOffer = new stdClass();
    $generalOffer->id = $key;
    $generalOffer->modified = null;
    $generalOfferArray[]=$generalOffer;
}

$stmt->close();
$mysqli->close();

$jObj = new stdClass();
$jObj->success = 1;
$jObj->generalOfferArray = $generalOfferArray;

$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
