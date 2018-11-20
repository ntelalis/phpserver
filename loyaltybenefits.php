//Benefits
$query = "SELECT lb.Name FROM LoyaltyTierBenefits ltb inner join LoyaltyBenefits lb on ltb.BenefitID=lb.ID WHERE TierID=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $tierID);
$stmt->execute();
$stmt->bind_result($benefit);
$stmt->store_result();

$benefits = array();
while ($stmt->fetch()) {
    $benefits[]=$benefit;
}
