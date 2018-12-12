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
//$_POST['check']= '[{"id":37,"modified":"2018-12-08 05:28:20"},{"id":8,"modified":"2019-01-01"}]';

if (isset($_POST['customerID'])) {
    $customerID = $_POST['customerID'];

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

    //Get all reservations (reservation info + occupancy + rating) for this customer which have not ended yet.
    $query = "SELECT res.ID, res.RoomTypeID, res.Adults, res.Children,
            res.DateBooked, res.StartDate, res.EndDate, o.CheckIn, o.CheckOut,
            r.Number, r.Floor, rat.Rating, rat.Comments,
            FROM_UNIXTIME(  ( UNIX_TIMESTAMP(res.Modified)
                            + UNIX_TIMESTAMP(COALESCE(o.Modified, res.Modified))
                            + UNIX_TIMESTAMP(COALESCE(rat.Modified, res.Modified))  ) / 3, '%Y-%m-%d %H:%i:%s') AS Modified
            FROM   Reservation res
                   LEFT JOIN Occupancy o
                          ON res.ID = o.ReservationID
                   LEFT JOIN Room r
                          ON r.ID = o.RoomID
                   LEFT JOIN Rating rat
                          ON res.ID = rat.ReservationID
            WHERE  res.CustomerID = ?
                   AND res.EndDate >= CURRENT_DATE";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $stmt->bind_result($id, $roomTypeID, $adults, $children, $bookDate, $startDate, $endDate, $checkIn, $checkOut, $roomNumber, $roomFloor, $rating, $ratingComments, $modified);
    $stmt->store_result();


    //initialize the array which will be sent to client
    $reservationArray = array();

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

        $reservation = new stdClass();
        //Reservation data
        $reservation->reservationId = $id;
        $reservation->roomTypeID = $roomTypeID;
        $reservation->adults = $adults;
        $reservation->children = $children;
        $reservation->bookedDate = $bookDate;
        $reservation->startDate = $startDate;
        $reservation->endDate = $endDate;
        //Occupation data
        $reservation->checkIn = $checkIn;
        $reservation->checkOut = $checkOut;
        $reservation->roomNumber = $roomNumber;
        $reservation->roomFloor = $roomFloor;
        //Rating data
        $reservation->rating = $rating;
        $reservation->ratingComments = $ratingComments;
        $reservation->modified = $modified;
        //add reservation to response array
        $reservationArray[] = $reservation;
    }

    //for each row that was sent by the client and server didn't find
    //a match with his query to database
    foreach ($values as $key => $value) {
        //add it to response array but only set modified date with null value
        //so the client will delete it from his list
        $reservation = new stdClass();
        $reservation->reservationId = $key;
        $reservation->modified = null;
        $reservationArray[]=$reservation;
    }

    //Close Connection to DB
    $stmt->close();
    $mysqli->close();

    //Build the json response
    $jObj->success = 1;
    $jObj->reservationArray = $reservationArray;
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
