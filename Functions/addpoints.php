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
  $query = "SELECT (SELECT IFNULL(SUM(lpeh.Points),0)
            FROM LoyaltyPointsEarningHistory lpeh
            WHERE lpeh.CustomerID = ? AND lpeh.DateEarned>=NOW()-INTERVAL 1 YEAR)
            -
            (SELECT IFNULL(SUM(lpsh.Points),0)
            FROM LoyaltyPointsSpendingHistory lpsh
            WHERE lpsh.CustomerID = ? AND lpsh.DateSpent>=NOW()-INTERVAL 1 YEAR)";
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

  $query = "SELECT Points FROM RoomTypePoints rtp WHERE rtp.RoomTypeID=? AND rtp.Persons=?";
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
  $query = "SELECT Points FROM RoomTypeCashPoints rtcp WHERE rtcp.RoomTypeID=? AND rtcp.Persons=? AND rtcp.CurrencyID=?";
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
