<?

require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<newRunner>";

if(isset($_POST['Election']) && $_POST['Election']!="" && 
    isset($_POST['Candidate']) && $_POST['Candidate']!="")
{
    $Election = $_POST['Election'];
    $Candidate = $_POST['Candidate'];

    $sql_connection->query("INSERT INTO running (Election, Candidate) VALUES ('$Election', '$Candidate')");
    $RunnerAdded = $sql_connection->affected_rows;
    if($RunnerAdded == 1)
    {
        echo "<status>OK</status>";
        echo "<RunnerID>".$sql_connection->insert_id."</RunnerID>";
        echo "<CandidateID>".$Candidate."</CandidateID>";
        echo "<ElectionID>".$Election."</ElectionID>";
        $RunningData = $sql_connection->query("SELECT candidates.Name, elections.Position FROM candidates, elections WHERE candidates.id = $Candidate AND elections.id = $Election")->fetch_object();
        echo "<Name>".$RunningData->Name."</Name>";
        echo "<Position>".$RunningData->Position."</Position>";
    }
    else
    {
        echo "<status>FAIL_TO_ADD_TO_DB</status>";
        echo "<DBError>".$sql_connection->error."</DBError>";
    }
    echo "<RunnerAdded>$RunnerAdded</RunnerAdded>";
}
else
    echo "<status>FAILED_MISSING_POSITION_NAME</status>";

echo "</newRunner>";

?>