<?
require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<electionData>";

$election = isset($_POST['election'])?$_POST['election']:null;

if(is_null($election))
{
    echo "<status>MISSING_ELECTION</status>";
}
else
{
    $gE = $sql_connection->query("SELECT * FROM elections WHERE id=$election");
    if($gE->num_rows == 1)
    {
        echo "<status>OK</status>";
        $eData = $gE->fetch_object();
        foreach ($eData as $key => $value) {
            echo "<$key>$value</$key>";
        }
    }
    else
        echo "<status>ELECTION_NOT_IN_DB</status>";
}
echo "</electionData>";


?>