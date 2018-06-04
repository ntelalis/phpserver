<?php


function addPointsByCustomerID($dbCon, $customerID, $pointsName, $quantity)
{
    $query = "INSERT INTO LoyaltyPointsEarningHistory(CustomerID,GainingPointsID,Quantity,DateEarned)
            SELECT ?,ID,?,NOW()
            FROM LoyaltyPointsEarningAction
            WHERE NAME=?";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('iis', $customerID, $quantity, $pointsName);
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function addPointsByReservationID($dbCon, $reservationID, $pointsName, $quantity)
{
    $query = "INSERT INTO LoyaltyPointsEarningHistory(CustomerID,GainingPointsID,Quantity,DateEarned)
            SELECT r.CustomerID,lpea.ID,?,NOW()
            FROM Reservation r,LoyaltyPointsEarningAction lpea
            WHERE NAME=? AND r.ID=?";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('isi', $quantity, $pointsName, $reservationID);
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function getFreeNightsPoints($dbCon,$roomTypeID,$persons){
  $query = "SELECT Points FROM RoomTypeFreeNightsPoints rtfnp WHERE rtfnp.RoomTypeID=? AND rtfnp.Persons=?";
  $stmt = $dbCon->prepare($query);
  $stmt-> bind_param('ii',$roomTypeID,$persons);
  if($stmt->execute()){
    $stmt->bind_result($points);
    $stmt->store_result();
    $stmt->fetch();
    return $points;
  }
  else{
    return NULL;
  }
}

function getCashNightsPoints($dbCon,$roomTypeID,$persons,$currencyID){
  $query = "SELECT Points FROM RoomTypePointsAndCash rtpac WHERE rtpac.RoomTypeID=? AND rtpac.Persons=? AND rtpac.CurrencyID=?";
  $stmt = $dbCon->prepare($query);
  $stmt-> bind_param('iii',$roomTypeID,$persons,$currencyID);
  if($stmt->execute()){
    $stmt->bind_result($points);
    $stmt->store_result();
    $stmt->fetch();
    return $points;
  }
  else{
    return NULL;
  }
}
