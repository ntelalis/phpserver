<?php

if (isset($_POST['check']) && !empty($_POST['check'])) {
    $jsonToCheck = json_decode($_POST['check']);
    $values = array();
    foreach ($jsonToCheck as $item) {
        $idClient = $item->id;
        $modifiedClient = $item->modified;
        $values[$idClient]=$modifiedClient;
    }
}

// $id and $modified from DB variables
while ($stmt->fetch()) {

  if (isset($values[$id])) {
      $timeInDB = strtotime($modified);
      $timeInClient = strtotime($values[$id]);
      unset($values[$id]);
      if (!($timeInDB>$timeInClient)) {
          continue;
      }
  }

  //go on
}

foreach($values as $key => $value){
  $roomType = new stdClass();
  $roomType->id = $key;
  $roomType->modified = null;
  $roomTypeArray[]=$roomType;
}

?>
