<?php

require 'dbConfig.php';

//Connection to Database
$dbCon = new mysqli($dbip,$dbusername,$dbpass,$dbname);

//Response Object
$jObj = new stdClass();

//Parse POST Variables
if(isset($_POST['pass'],$_POST['email'],$_POST['code']) && !empty($_POST['pass']) && !empty($_POST['email']) && !empty($_POST['code'])){
  $pass = $_POST['pass'];
  $email = $_POST['email'];
  $code = $_POST['code'];

  //Check if email matches a record in database and return customerID,Verification Code and VerificationTime
  $query = "SELECT CustomerID,Verify,VerifyTime FROM Account WHERE Email=?";

  $stmt = $dbCon->prepare($query);

  $stmt->bind_param('s',$email);

  $stmt->execute();
  $stmt->bind_result($customerID,$verificationDB,$VerifyTime);
  $stmt->fetch();
  $stmt->close();

  //Current Time
  $now = time();
  //Verification Time
  $timeDB = strtotime($VerifyTime);
  //Seconds Passed
  $diff = $now - $timeDB;

  //Check if threshold hasn't passed
  if($diff<3600){
    if($code==$verificationDB){

      $hash = password_hash($pass,PASSWORD_DEFAULT);

      //Set new Password and remove Verification Code for the account in database
      $query = "UPDATE Account SET Hash = ?, Verify = NULL WHERE CustomerID = ?";
      $stmt = $dbCon->prepare($query);
      $stmt->bind_param('si',$hash,$customerID);
      $success = $stmt->execute();
      $stmt->close();

      if($success){
        //Success
        $jObj->success=1;
      }
      else{
        //Nope
        $jObj->success=2;
      }
    }
    else{
      //Fail: Verification Doesn't match
      $jObj->success=4;

    }
  }
  else{
    //Fail: Time has passed
    $jObj->success=5;
  }

  //Close Connection
  $dbCon->close();

}
else{
  //Variables not set
  $jObj->success=6;
}

//Encode data in JSON Format
$JsonResponse = json_encode($jObj);

//Show Data
echo $JsonResponse;

?>
