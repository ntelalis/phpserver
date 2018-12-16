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
//$_POST['menuTime'] = "Μεσημεριανό";

if (isset($_POST['menuTime'])) {

    $menuTime = $_POST['menuTime'];

    //get all available foods for this menutime
    $query = "  SELECT f.ID,f.Name,f.Description,f.Price,fmc.Name
                FROM Food f, FoodMenuTime fmt, FoodMenuCategory fmc, FoodMenuItem fmi
                WHERE f.ID = fmi.FoodID AND fmt.ID = fmi.FoodMenuTimeID  AND fmc.ID = fmi.FoodMenuCategoryID
                AND f.Availability = 1 AND fmt.Name = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $menuTime);
    $stmt->execute();
    $stmt->bind_result($id, $name, $desc, $price, $category);
    $stmt->store_result();

    while ($stmt->fetch()) {
        //if specific category doesn't exist as a varible
        if (!isset(${$category})) {
            //create a variable with the name of the category
            ${$category} = array();
            //add it to a helper string table containg all categories
            $categoryNameArray[] = $category;
        }

        //create the food Object
        $food = new stdClass();
        $food->id = $id;
        $food->name = $name;
        $food->desc = $desc;
        $food->price = $price;
        //add it to the correct category array
        ${$category}[] = $food;
    }

    //create the response array which will be array of objects
    $categoryArray = array();
    //for all the categories that were found (find from the helper array)
    foreach ($categoryNameArray as $categoryName) {
        //create a new object
        $category = new stdClass();
        //this object has one field (which is named after the category)
        //and its value is the corresponding array of objects (foods)
        $category->{$categoryName} = ${$categoryName};
        //$category->Κρύα ορεκτικα = $Κρύα ορεκτικά;
        //add it to the response array
        $categoryArray[] = $category;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();

    //Build the json response
    $jObj->categoryArray = $categoryArray;
}
//Bad request
else {
    $jObj->success = 0;
    $jObj->errorMessage = "Bad Request";
}

//Specify that the response is json in the header
header('Content-type:application/json;charset=utf-8');

//Encode the JSON Object and print the result
$JsonResponse = json_encode($jObj, JSON_UNESCAPED_UNICODE);
echo $JsonResponse;
