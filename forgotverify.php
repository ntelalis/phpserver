<?php

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if(isset($_POST['verification'], $_POST['email']) && !empty($_POST['verification']) && !empty($_POST['email'])){

  $email = $_POST['email'];
  $verification = $_POST['verification'];

  //Check if email matches a record in database and return customerID,Verification Code and VerificationTime
  $query = "SELECT CustomerID,Verify,VerifyTime FROM Account WHERE Email=?";

  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('s',$email);
  $stmt->execute();
  $stmt->bind_result($customerID,$verificationDB,$VerifyTime);
  $stmt->store_result();
  $stmt->fetch();
  $numrows = $stmt->num_rows;

  //Current Time
  $now = time();
  //Verification Time
  $timeDB = strtotime($VerifyTime);
  //Seconds Passed
  $diff = $now - $timeDB;

  //Check if threshold hasn't passed
  if($diff<3600){
    if($verification==$verificationDB){

      //Success
      $jObj->success=1;
    }
    else{

      //Fail: Verification Doesn't match
      $jObj->success=0;
    }
  }
  else{
    //Fail: Time has passed
    $jObj->success=0;
  }


  //Close Connections
  $stmt->close();
  $dbCon->close();

}
else{
  //Variables not set
  $jObj->success=0;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;

?>
