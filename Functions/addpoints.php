<?php

function addPointsByReservationID($mysqli, $reservationID, $pointsName, $quantity)
{
    $query = "INSERT INTO LoyaltyPointsEarningHistory(CustomerID,GainingPointsID,Points,DateEarned)
            SELECT r.CustomerID,lpea.ID,rtp.GainingPoints*?,NOW()
            FROM Reservation r,LoyaltyPointsEarningAction lpea, RoomTypePoints rtp
            WHERE lpea.Name=? AND r.ID=? AND rtp.RoomTypeID=r.RoomTypeID AND rtp.Adults=r.Adults";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isi', $quantity, $pointsName, $reservationID);
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function subPointsByCustomerID($mysqli, $customerID, $pointsName, $quantity)
{
    $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Quantity,DateSpent)
            SELECT ?,ID,?,NOW()
            FROM LoyaltyPointsSpendingAction lpsa
            WHERE lpsa.Name=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('iis', $customerId, $quantity, $pointsName);
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function subPointsByReservationID($mysqli, $reservationID, $pointsName, $quantity)
{
    $query = "INSERT INTO LoyaltyPointsSpendingHistory(CustomerID,SpendingPointsID,Quantity,DateSpent)
            SELECT r.CustomerID,lpsa.ID,?,NOW()
            FROM Reservation r,LoyaltyPointsSpendingAction lpsa
            WHERE lpsa.Name=? AND r.ID=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('isi', $quantity, $pointsName, $reservationID);
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function getPointsByCustomerID($mysqli, $customerID)
{
    $query = "SELECT (SELECT IFNULL(SUM(lpeh.Points),0)
            FROM LoyaltyPointsEarningHistory lpeh
            WHERE lpeh.CustomerID = ? AND lpeh.DateEarned>=NOW()-INTERVAL 1 YEAR)
            -
            (SELECT IFNULL(SUM(lpsh.Points),0)
            FROM LoyaltyPointsSpendingHistory lpsh
            WHERE lpsh.CustomerID = ? AND lpsh.DateSpent>=NOW()-INTERVAL 1 YEAR)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ii', $customerID, $customerID);
    $stmt->execute();
    $stmt->bind_result($points);
    $stmt->store_result();
    $stmt->fetch();

    return $points;
}

function getSpendingActionPointsByName($mysqli, $name)
{
    $query = "SELECT Points FROM LoyaltyPointsSpendingAction WHERE Name=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->bind_result($points);
    $stmt->store_result();
    $stmt->fetch();
    return $points;
}

function getFreeNightsPoints($mysqli, $roomTypeID, $persons, $children)
{
    $query = "SELECT SpendingPoints FROM RoomTypePoints rtp WHERE rtp.RoomTypeID=? AND rtp.Adults=? AND rtp.Children=?";
    $stmt = $mysqli->prepare($query);
    $stmt-> bind_param('iii', $roomTypeID, $persons, $children);
    if ($stmt->execute()) {
        $stmt->bind_result($points);
        $stmt->store_result();
        $stmt->fetch();
        return $points;
    } else {
        return null;
    }
}
function getCashNightsPoints($mysqli, $roomTypeID, $persons, $children, $currencyID)
{
    $query = "SELECT Points FROM RoomTypeCashPoints rtcp WHERE rtcp.RoomTypeID=? AND rtcp.Adults=? AND rtcp.Children=? AND rtcp.CurrencyID=?";
    $stmt = $mysqli->prepare($query);
    $stmt-> bind_param('iiii', $roomTypeID, $persons, $children, $currencyID);
    if ($stmt->execute()) {
        $stmt->bind_result($points);
        $stmt->store_result();
        $stmt->fetch();
        return $points;
    } else {
        return null;
    }
}
