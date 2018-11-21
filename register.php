<?php

//DEBUG
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

//Response Object
$jObj = new stdClass();

//DEBUG
//$_POST['email'] = "gpaschos@epi.com.gr";
//$_POST['pass'] = "Qqwerty1!";
//$_POST['firstName'] = "George";
//$_POST['lastName'] = "Paschos";
//$_POST['titleID'] = 2;
//$_POST['countryID'] = 4;
//$_POST['birthDate'] = "1991-11-02";
//$_POST['phone'] = "6987453152";

if(isset($_POST['email'],$_POST['pass'],$_POST['firstName'],$_POST['lastName'],
$_POST['titleID'],$_POST['countryID'],$_POST['birthDate'],
$_POST['phone'])){

    $email = $_POST['email'];
    $pass = $_POST['pass'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $titleID = $_POST['titleID'];
    $countryID = $_POST['countryID'];
    $birthDate = $_POST['birthDate'];
    $phone = $_POST['phone'];

    //begin transaction
    $mysqli->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

    //insert customer related data
    $query = "INSERT INTO Customer(TitleID,FirstName,LastName,BirthDate,CountryId) VALUES(?,?,?,?,?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isssi', $titleID, $firstName, $lastName, $birthDate, $countryID);
    $success = $stmt->execute();

    if($success){
        //if success get the customer id
        $customerID = $mysqli->insert_id;

        //hash the password
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        //insert it into database along with email
        $query = "INSERT INTO Account(CustomerID,Email,Hash) VALUES(?,?,?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('iss', $customerID, $email, $hash);
        $success = $stmt->execute();

        if($success){
            $mysqli->commit();
            //if success return customer ID
            $jObj->success=1;
            $jObj->customerID=$customerID;
        }
        else{
            //inserting into Account failed
            $mysqli->rollback();
            $jObj->success=0;
            $jObj->errorMessage=$mysqli->errorMessage;
        }
    }
    else{
        //inserting into Customer failed
        $mysqli->rollback();
        $jObj->success=0;
        $jObj->errorMessage=$mysqli->error;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();
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
