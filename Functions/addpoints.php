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
