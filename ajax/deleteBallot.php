<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<deleteBallot>";

if(isset($_POST['Election']) && 
    $_POST['Election']!="" && 
    isset($_POST['BallotNr']) && $_POST['BallotNr']!="")
{
    $Election = $_POST['Election'];
    $BallotNr = $_POST['BallotNr'];

    $sql_connection->query("DELETE FROM ballots WHERE Election=$Election AND ballotNrPerElection = $BallotNr");
    if($sql_connection->affected_rows == 1)
    {
        echo "<status>OK</status>";
    }
    else
        echo "<status>FAILED_TO_REMOVE_BALLOT</status>";
}
else
    echo "<status>FAILED_MISSING_ELECTION_OR_BALLOT_NUMBER</status>";

echo "</deleteBallot>";

?>