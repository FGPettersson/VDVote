<?
require_once('./functions-mysql.php');
require_once('./functions-other.php');

$sql_connection = connectToDatabase();

print_html_header("<link rel='stylesheet' href='css/indexstyle.css'>");  

echo "

    <div id='selectElections' class='centerBox'>
        <div id='onGoingElections' class='myList shaded lfloat'>
        <h2>Aktuella val</h2>
        <input type='text' id='newElection' placeholder='Lägg till nytt val' />
        <ul id='electionList'>";


?>
        </ul>
        </div>
        <div id='closedElections' class='myList shaded lfloat'>
        <h2>Avslutade val</h2>
        <!-- <ul>
            <li>
                <a href='#'>
                <h3>Hovmästare</h3>
                <p></p>
                </a>
            </li>
            <li>
                <a href='#'>
                <h3>Pubchef</h3>
                <p></p>
                </a>
            </li>
        </ul> -->
        </div>
    </div>
</div>

<?

print_html_footer();
?>

<script type='text/javascript' language='javascript'>
var firstGet = true;

function getElections()
{
    var gE = $.post('./ajax/getAllElections.php');
    gE.done(function(xml){
        var allelections = $(xml).find("election").each(function(){
            var eID = $(this).find("ElectionID").text();
            var position = $(this).find("Position").text();
            var details = $(this).find("Details").text();
            if($('#e_'+eID).length)
            {} // This election is already displayed and dosen't need to be added.
            else
            {
                var liStr = "<li id='e_"+eID+"'><a href='./election.php?election="+eID+"'><h3>"+position+"</h3><p>"+details+"</p></a></li>";
                if(firstGet == true)
                {
                    $(liStr).appendTo('#electionList');
                }
                else
                {
                    $(liStr).hide().appendTo('#electionList').slideDown("fast");
                }

            }
        });
    firstGet = false;
    return false;
    });
}

$('#newElection').keypress(function (e) {
    if (e.which == 13) {
        e.preventDefault();
        var Title = $('#newElection').val();
        var addE = $.post('./ajax/addElection.php', { Position: Title });
        addE.done(function(xml){
            getElections();
        });
        $('#newElection').val("");
        // getRunners();
    }
});

$(document).ready(function(){
    getElections();
});

</script>