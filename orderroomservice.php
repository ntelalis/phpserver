<?php

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);
$dbCon->set_charset("utf8");
//Response Object
$jObj = new stdClass();

//Parse POST Variables
if(isset($_POST['order']) && $_POST['reservationID']){
  $reservationId = $_POST['reservationID'];
  $order = $_POST['order'];
  $comment = $_POST['comment'];

  $json = json_decode($order);
  $list = $json->order;

  $query = "INSERT INTO RoomServiceOrder(ReservationID,OrderDate,Comment) VALUES(?,NOW(),?)";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('is',$reservationId,$comment);
  $success = $stmt->execute();
  $orderid = $dbCon->insert_id;


  $query = "INSERT INTO RoomServiceOrderItem(OrderID,FoodID,Quantity) VALUES(?,?,?)";
  $stmt = $dbCon->prepare($query);
  foreach ($list as $item) {
    $stmt->bind_param('iii',$orderid,$item->id,$item->quantity);
    $success = $stmt->execute();
  }

  $query = "SELECT SUM(Quantity*Price) FROM RoomServiceOrderItem INNER JOIN Food f ON FoodID=ID WHERE OrderID=?";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('i',$orderid);
  $success = $stmt->execute();
  $stmt->bind_result($totalPrice);
  $stmt->store_result();
  $stmt->fetch();

  $query = "INSERT INTO Charge(ReservationID,ChargedServiceID,Price) SELECT ?,ID,? FROM ChargedService WHERE Tag='roomservice'";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('id',$reservationId,$totalPrice);
  $success = $stmt->execute();

  $jObj->success=1;

}
else{
  $jObj->success=0;
  $jObj->errorMessage="$dbCon->error";
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj,JSON_UNESCAPED_UNICODE);

//Show Data
echo $JsonResponse;

?>
