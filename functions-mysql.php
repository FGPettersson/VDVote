<?
function connectToDatabase()
{
	$sql_user = 'webpage';
	$sql_pass = 'pt7QxryaPZZah4bv';
	$sql_host = 'localhost';
	$sql_db = 'VDVote';

	$sql_connection = new mysqli($sql_host, $sql_user, $sql_pass, $sql_db);

	$sql_connection->query("SET NAMES 'utf8'") or die(mysql_error());
	$sql_connection->query("SET CHARACTER SET 'utf8'") or die(mysql_error()); 

	if($sql_connection->connect_errno)
	{
		die('Kunde inte ansluta till databasen (' . $sql_connection->connect_error . ') ' . mysqli_connect_error());	
	}

	//logg("Uppkopplad till databasen. Yay!");

	return $sql_connection;
}
?>