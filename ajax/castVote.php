<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<newVote>";

if(isset($_POST['Ballot']) && $_POST['Ballot']!="" && 
    isset($_POST['Candidate']) && $_POST['Candidate']!="" &&
    isset($_POST['Rank']) && $_POST['Rank']!="")
{
    $Ballot = $_POST['Ballot'];
    $Candidate = $_POST['Candidate'];
    $Rank = $_POST['Rank'];

    

    $sql_connection->query("INSERT INTO votes (Ballot, Rank, Candidate) VALUES ($Ballot, $Rank, $Candidate)");
    $voteAdded = $sql_connection->affected_rows;
    if($voteAdded == 1)
    {
        echo "<status>OK</status>";
        echo "<voteID>".$sql_connection->insert_id."</voteID>";
        echo "<CandidateID>".$Candidate."</CandidateID>";
        echo "<ballotID>".$Ballot."</ballotID>";
        echo "<rankID>".$Rank."</rankID>";
    }
    else
    {
        echo "<status>FAIL_TO_ADD_TO_DB</status>";
        echo "<DBError>".$sql_connection->error."</DBError>";
    }
    echo "<voteAdded>$voteAdded</voteAdded>";
}
else
    echo "<status>FAILED_MISSING_BALLOT_CANDIDATE_OR_RANK</status>";

echo "</newVote>";

?>