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
//$_POST['email'] = 'ntelalis@gmail.com';
//$_POST['pass'] = 'Qqwerty1!';
//$_POST['modified'] = '2018-09-05 20:41:00';

if(isset($_POST['email'],$_POST['pass'])){

    $email = $_POST['email'];
    $pass = $_POST['pass'];

    //Get customerID and Hash for this email
    $query = "SELECT a.CustomerID, a.Hash, a.Verified FROM Account a, Customer c WHERE a.Email = ? AND c.ID=a.CustomerID";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($customerID,$hash,$verified);
    $stmt->store_result();
    $stmt->fetch();

    //check if customer is found
    if($stmt->num_rows == 1){
        //Check if hash matches the password
        if(password_verify($pass, $hash)){
            //check if email is verified
            if($verified==1) {

                //Login success
                $jObj->success = 1;

                //get all necessary customer data
                $query = "SELECT t.Title, c.FirstName, c.LastName, c.BirthDate, co.Name,
                ci.Address1, ci.Address2, ci.City, ci.PostalCode, ci.Phone,
                (SELECT COUNT(o.ReservationID)
                FROM   Occupancy o,
                Reservation r
                WHERE  r.CustomerID = c.ID
                AND o.ReservationID = r.ID
                AND o.CheckOut IS NOT NULL)          AS finishedStays,
                GREATEST(c.Modified, IFNULL(ci.Modified, 0)) AS Modified
                FROM   Country co,
                Title t,
                Customer c
                LEFT JOIN ContactInfo ci
                ON c.ID = ci.CustomerID
                WHERE  c.ID = ?
                AND co.ID = c.CountryID
                AND t.ID = c.TitleID";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param('i', $customerID);
                $stmt->execute();
                $stmt->bind_result($title, $firstName, $lastName, $birthDate, $country, $address1, $address2, $city, $postalCode, $phone, $finishedStays, $modifiedDB);
                $stmt->store_result();
                $stmt->fetch();

                //Close Connection to DB
                $stmt->close();
                $mysqli->close();

                //Check if customer has up to date data
                if(isset($_POST['modified'])){
                    $modifiedClient = $_POST['modified'];
                    $timeInDB = strtotime($modifiedDB);
                    $timeInClient = strtotime($modifiedClient);
                }

                //If he isn't uptodate, return the results with the login success message
                if (!isset($modifiedClient) || $timeInDB!=$timeInClient) {
                    $jObj->customerID = $customerID;
                    $jObj->title = $title;
                    $jObj->firstName = $firstName;
                    $jObj->lastName = $lastName;
                    $jObj->birthDate = $birthDate;
                    $jObj->country = $country;
                    $jObj->phone = $phone;
                    $jObj->address1 = $address1;
                    $jObj->address2 = $address2;
                    $jObj->city = $city;
                    $jObj->postalCode = $postalCode;
                    //Check if Customer has revisited the hotel
                    $jObj->oldCustomer = $finishedStays != 0;
                    $jObj->modified = $modifiedDB;
                }
            }
            //Email not verified yet
            else{
                $jObj->success = 0;
                $jObj->errorMessage = "Please verify your email";
            }
        }
        //Password not correct
        else{
            $jObj->success = 0;
            $jObj->errorMessage = "Login failed";
        }
    }
    //Customer not found
    else{
        $jObj->success = 0;
        $jObj->errorMessage = "Login failed";
    }
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
