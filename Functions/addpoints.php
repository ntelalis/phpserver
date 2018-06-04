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

function subPointsByCustomerID($dbCon, $customerID, $pointsName, $quantity)
{
  $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Quantity,DateSpent)
            SELECT ?,ID,?,NOW()
            FROM LoyaltyPointsSpendingAction lpsa
            WHERE lpsa.Name=?";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('iis', $customerId, $quantity, $pointsName);
  if ($stmt->execute()) {
      return true;
  } else {
      return false;
  }
}

function subPointsByReservationID($dbCon, $reservationID, $pointsName, $quantity)
{
    $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Quantity,DateSpent)
            SELECT r.CustomerID,lpsa.ID,?,NOW()
            FROM Reservation r,LoyaltyPointsSpendingAction lpsa
            WHERE lpsa.Name=? AND r.ID=?";
    $stmt = $dbCon->prepare($query);
    $stmt->bind_param('isi', $quantity, $pointsName, $reservationID);
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function getPointsByCustomerID($dbCon,$customerID){
  $query = "SELECT IFNULL((SELECT sum(a1.Points*h.Quantity)
  FROM LoyaltyPointsEarningHistory h JOIN LoyaltyPointsEarningAction a1 on h.GainingPointsID=a1.ID WHERE h.CustomerID=? and h.DateEarned>=Now()-INTERVAL 1 YEAR),0) - IFNULL((SELECT sum(a2.Points*h.Quantity) FROM LoyaltyPointsSpendingHistory h JOIN LoyaltyPointsSpendingActionRoomType a2 on h.SpendingPointsID=a2.ID WHERE h.CustomerID=? and h.DateSpent>=Now()-INTERVAL 1 YEAR),0) as Points";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('ii', $customerID, $customerID);
  $stmt->execute();
  $stmt->bind_result($points);
  $stmt->store_result();
  $stmt->fetch();
  return $points;
}

function getSpendingActionPointsByName($dbCon,$name){
  $query = "SELECT Points FROM LoyaltyPointsSpendingAction WHERE Name=?";
  $stmt = $dbCon->prepare($query);
  $stmt->bind_param('s', $name);
  $stmt->execute();
  $stmt->bind_result($points);
  $stmt->store_result();
  $stmt->fetch();
  return $points;
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
