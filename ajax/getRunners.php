<?
require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<runners>";

$election = isset($_POST['election'])?$_POST['election']:null;

if(is_null($election))
{
    echo "<status>MISSING_ELECTION</status>";
}
else
{
    if($sql_connection->query("SELECT id FROM elections WHERE id=$election")->num_rows == 1)
    {
        echo "<status>OK</status>";
        $runners = $sql_connection->query("SELECT candidates.*, running.Candidate as cID FROM candidates, running WHERE running.Election=$election and running.Candidate = candidates.id");
        echo "<numberOfRunners>$runners->num_rows</numberOfRunners>";
        while($runner = $runners->fetch_object())
        {
            echo "<runner>";
            echo "<CandidateID>$runner->cID</CandidateID>";
            echo "<Name>$runner->Name</Name>";
            echo "</runner>";
        }
    }
    else
        echo "<status>ELECTION_NOT_IN_DB</status>";
}
echo "</runners>";


?>