<?php

require 'dbConfig.php';


$mysqli = new mysqli($dbip, $dbusername, $dbpass, $dbname);
$mysqli->set_charset("utf8");

$_POST['timeType'] = "Μεσημεριανό";

if (isset($_POST['timeType'])) {
    $timeType = $_POST['timeType'];
    $jObj = new stdClass();

    $query = "SELECT f.ID,f.Name,f.Description,f.Price,fmc.ID,fmc.Name
  FROM Food f, FoodMenuTime fmt, FoodMenuCategory fmc, FoodMenuItem fmi
  WHERE f.ID = fmi.FoodID AND fmt.ID = fmi.FoodMenuTimeID  AND fmc.ID = fmi.FoodMenuCategoryID
  AND f.Availability = 1 AND fmt.Name = ?";

    $stmt = $mysqli->prepare($query);
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
    $mysqli->close();

    $jObj->success=1;
    $jObj->typeCategory = $finalarray;
} else {
    $jObj->success=0;
    $jObj->errorMessage=$mysqli->error;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);

//Show Data
echo $JsonResponse;
