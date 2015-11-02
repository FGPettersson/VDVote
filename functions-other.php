<?
require_once('./functions-mysql.php');
function print_html_header($inside_header = null)
{

echo "
<!doctype html>
<html>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'>
        <meta name='description' content=''>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>V-Dala personval</title>
        <link rel='stylesheet' href='css/reset.css'>
        <link rel='stylesheet' href='css/main.css'>
        <script src='./js/jquery-1.10.2.js' type='text/javascript'></script>
        <script src='./js/someJS.js' type='text/javascript'></script>
        <script src='./js/jquery.sortable.js' type='text/javascript'></script>
";
echo (isset($inside_header)?$inside_header:"");

$election = NULL;
$getStr = "";
$title = "VDVote - Personval för V-Dala";

if(isset($_GET['election'])&&$_GET['election']!="")
{
    $election = $_GET['election'];
    $getStr = "?election=$election";
    $sql_connection = connectToDatabase();
    $gp_res = $sql_connection->query("SELECT Position from elections WHERE id=$election");
    if($gp_res->num_rows == 1)
    {
        $title = $gp_res->fetch_object()->Position;
    }
    else
    {
        $nonExistingElectionID = true;
        $getStr = "";
    }
}

$filename = substr(strrchr($_SERVER['PHP_SELF'], "/"), 1);
$current = ["index.php"=>"", "election.php"=>"", "vote.php"=>"", "calculateWinners.php"=>""];
$current[$filename] = " class='current'";

echo "</head><body><div id='container'>

<header class='shaded'>
    <h1>$title<a href='#'><a href='#' class='delLink' id='removeElection'><img src='./img/deleteWhite.png'></a>
        <div id='delConfirm'>
            <div id='delConfirmContent'>
                Är du säker på att du vill radera valet?<br />
                <a href='#' id='delConfirmYes'>Ja</a>
                <a href='#' id='delConfirmNo'>Nej</a>
            </div>
        </div>
</h1>
    <nav>
        <ol>
            <li><a href='./index.php$getStr'".$current['index.php']."><img src='./img/users2.png'>Val</a></li>
            <li><a href='./election.php$getStr'".$current['election.php']."><img src='./img/settings48.png'>Inställningar</a></li>
            <li><a href='./vote.php$getStr'".$current['vote.php']."><img src='./img/political5.png'>Rösta</a></li>
            <li><a href='./calculateWinners.php$getStr'".$current['calculateWinners.php']."><img src='./img/position5.png'>Resultat</a></li>
        </ol>
    </nav>
    
</header>";
if(!is_null($election))
{
echo "
    <script type='text/javascript' language='javascript'>
    function removeElection(){
        var electionID = $election;
        remC = $.post('./ajax/removeElection.php', { election: electionID });
        remC.done(function(xml){
            if($(xml).find('status').text() == 'OK')
                location.href = './index.php';
        });
    }

    $('.delLink').click(function(){
        $('#delConfirm').show();
    });
    $('#delConfirmNo').click(function(){
        $('#delConfirm').hide();
    });
    $('#delConfirmYes').click(function(){
        $('#delConfirm').hide();
        removeElection();
    });
    </script>
";
}

echo "
<div id='main'>
";

if(($filename != "index.php" && is_null($election)) || isset($nonExistingElectionID))
{
    die("<div id='noElectionWarning'>Du måste välja ett val att arbeta med.</div>");
}
flush();



}
function print_html_footer()
{

// echo "
//     </div><footer class='shaded'>
//     <a href='mailto:f.g.pettersson@gmail.com'>f.g.pettersson@gmail.com</a>
//     </footer>
    echo "<div class='clear'> </div>";
echo"    </body>
";
	
}
?>