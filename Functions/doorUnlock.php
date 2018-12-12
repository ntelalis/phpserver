<?php

/*
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
*/

function doorUnlock($roomID)
{
    //DEBUG CODE
    return true;

    //this function sends a message to door to unlock through external api
    $socket = initConnection();
    $msgSend = "room_door";
    $msgReceived = "";
    $ads = socket_write($socket, $msgSend, strlen($msgSend));
    $msgReceived = socket_read($socket, 2048);

    if($msgReceived == "OK"){
      return true;
    }
    else{
      return false;
    }
}

function initConnection(){

  $address1 = "192.168.102.239";
  $port1 = 54325;

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if($socket === false){
      return false;
    }
    $result = socket_connect($socket, $address1, $port1);
    if($result === false){
      return false;
    }
    return $socket;
}
