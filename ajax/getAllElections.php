<?
require_once('../functions-mysql.php');

$sql_connection = connectToDatabase();

header("Content-type: text/xml");
echo "<?xml version='1.0' encoding='UTF-8'?>";
echo "<allElections>";

$elections = $sql_connection->query("SELECT * FROM elections WHERE Finished=0 AND Deleted=0");
if($elections->num_rows > 0)
{
    echo "<status>OK</status>";
    echo "<numberOfElections>".$elections->num_rows."</numberOfElections>";
    while($election = $elections->fetch_object())
    {
        echo "
        <election>
            <ElectionID>".$election->id."</ElectionID>
            <Position>".$election->Position."</Position>
            <Details>".$election->Details."</Details>
        </election>";
    }
}
else
    echo "<status>COULD_NOT_FIND_ANY_ELECTIONS</status>";

echo "</allElections>";


?>