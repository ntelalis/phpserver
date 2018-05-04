<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function getEmailServer(){

  //Setup PHPΜailer for SMTP
  $mail = new PHPMailer(true);
  $mail->isSMTP();

  //STMP Options
  $mail->SMTPAuth = true;

  //SMTP Server Settings
  $mail->Host = "smtp.gmail.com";
  $mail->Port = 465;
  $mail->SMTPSecure = 'ssl';

  //Email login credentials
  $mail->Username = "hotelapplicationbeacon";
  $mail->Password = "asdf1asdf1!";

  //Send From
  $mail->setFrom("hotelapplicationbeacon@gmail.com");

  return $mail;
}

 ?>
