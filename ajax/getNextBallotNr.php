<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<nextBallot>";

if(isset($_POST['Election']) && $_POST['Election']!="")
{
    $Election = $_POST['Election'];
    $new_ballot_per_election_nr = 1;
    $elExist = $sql_connection->query("SELECT count(*) as ex FROM elections WHERE id=$Election");
    if($elExist->fetch_object()->ex == 1)
    {
        $posBallotNr = $sql_connection->query("SELECT ballotNrPerElection FROM ballots WHERE Election=$Election ORDER BY ballotNrPerElection DESC LIMIT 1");
        if($posBallotNr->num_rows == 1)
        {
            $new_ballot_per_election_nr = $posBallotNr->fetch_object()->ballotNrPerElection + 1;
        }
            echo "<status>OK</status>";
            echo "<ballotNr>$new_ballot_per_election_nr</ballotNr>";
    }
    else
        echo "<status>NON_EXISTING_ELECTION</status>";
}
else
    echo "<status>NO_ELECTION_INPUT</status>";

echo "</nextBallot>";

?>