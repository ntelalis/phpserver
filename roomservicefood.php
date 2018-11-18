FoodServingTime<?php

include 'dbConfig.php';

$dbCon = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$dbCon->set_charset("utf8");

if (isset($_POST['timeType'])) {
    $timeType = $_POST['timeType'];
    $jObj = new stdClass();

    $query = "SELECT f.ID,f.Name,f.Description,f.Price,fc.ID,fc.Name
  FROM Food f, FoodTimeZone ftz, FoodCategory fc, FoodServingTime fst
  WHERE f.ID = fst.FoodID AND ftz.ID = fst.FoodTimeZoneID  AND fc.ID = fst.FoodCategoryID
  AND f.Availability = 1 AND ftz.Name = ?";

    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('s', $timeType);
    $stmt->execute();
    $stmt->bind_result($foodID, $foodName, $foodDesc, $foodPrice, $categoryID, $categoryName);
    $stmt->store_result();
    $finalarray = array();
    $typeCategory = array();

    while ($stmt->fetch()) {
        $categoryObj = new stdClass();

        if (!isset(${$categoryName})) {
            ${$categoryName} = array();
            $typeCategory[] = $categoryName;
        }

        $foodObj = new stdClass();
        $foodObj->id = $foodID;
        $foodObj->name = $foodName;
        $foodObj->desc = $foodDesc;
        $foodObj->price = $foodPrice;
        ${$categoryName}[] = $foodObj;
    }
    $results = array();

    for ($i=0;$i<count($typeCategory);$i++) {
        $huh = new stdClass();
        $huh->{$typeCategory[$i]} = ${$typeCategory[$i]};
        $finalarray[] = $huh;
    }

    $stmt->close();
    $dbCon->close();

    $jObj->success=1;
    $jObj->typeCategory = $finalarray;
} else {
    $jObj->success=0;
    $jObj->errorMessage=$dbCon->error;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);

//Show Data
echo $JsonResponse;
