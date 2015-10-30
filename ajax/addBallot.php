<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<newBallot>";

if(isset($_POST['Election']) && $_POST['Election']!=""){
    $Election = $_POST['Election'];

    $voteTime = time(); 
    
    if(isset($_POST['ballotNr']))
    {
        $new_ballot_per_election_nr = $_POST['ballotNr'];
    }
    else
    {
        $new_ballot_per_election_nr = 1;
        $posBallotNr = $sql_connection->query("SELECT ballotNrPerElection FROM ballots WHERE Election=$Election ORDER BY ballotNrPerElection DESC LIMIT 1");
        if($posBallotNr->num_rows == 1)
        {
            $new_ballot_per_election_nr = $posBallotNr->fetch_object()->ballotNrPerElection + 1;
        }
    }

    $sql_connection->query("INSERT INTO ballots (Election, VoteTime, ballotNrPerElection) VALUES ('$Election', $voteTime, $new_ballot_per_election_nr)");
    $BallotsAdded = $sql_connection->affected_rows;
    if($BallotsAdded == 1)
    {
        echo "<status>OK</status>";
        echo "<BallotID>".$sql_connection->insert_id."</BallotID>";
    }
    else
    {
        echo "<status>FAIL_TO_ADD_TO_DB</status>";
        echo "<DBError>".$sql_connection->error."</DBError>";
    }
    echo "<ElectionID>$Election</ElectionID>";
    echo "<BallotsAdded>$BallotsAdded</BallotsAdded>";
}
else
    echo "<status>FAILED_MISSING_ELECTION_NUMBER</status>";

echo "</newBallot>";

?>