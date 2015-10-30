<?

require_once('./functions-mysql.php');
require_once('./functions-other.php');

$sql_connection = connectToDatabase();
print_html_header("<link rel='stylesheet' href='css/votestyle.css'>");  
$election = isset($_GET['election'])?$_GET['election']:null;
$title = "Inget val är valt";
if(isset($election))
{
    $electionRes = $sql_connection->query("SELECT * FROM elections WHERE id=$election");
    if($electionRes->num_rows == 1)
    {
        $electionData = $electionRes->fetch_object();
        $title = $electionData->Position;

        $candidatesData = $sql_connection->query("SELECT candidates.*, running.Candidate as cID FROM candidates, running WHERE running.Election=$election and running.Candidate = candidates.id");
    }
    else
    {
        $electionFail = true;
        $title = "Kunde inte hitta valt val";        
    }

}

echo "
<header class='shaded'>
    <ol>
    <li><a href='./index.php' class='backLink firstLink'><img src='./img/users2.png'>Alla val</a></li>
    <li><a href='./election.php?election=$election' class='backLink secondLink'><img src='./img/settings48.png'>Inställningar</a></li>
    </ol>
    <a href='#' class='delLink' id='removeBallotsLink'><img src='./img/delete96.png'>Ta bort samtliga röster</a>
            <div id='delConfirm'>
            <div id='delConfirmContent'>
                Är du säker på att du vill ta bort alla röster?<br />
                <a href='#' id='delConfirmYes'>Ja</a>
                <a href='#' id='delConfirmNo'>Nej</a>
            </div>
        </div>

    <h1>$title</h1>
</header>

<div id='main'>
    <div class='centerBox relative'>
        <div class='myList' >
            <h2>Kandidater</h2>
            <ol id='listOfRunners'>";
$i = 0;
$charList = "123456789QWERTYUIOPASDFGHJKLZXCVBNM";
while($cData = $candidatesData->fetch_object())
{
    $lID = substr($charList, $i, 1);
    echo "
                <li data-cID='".$cData->cID."' data-listID='$lID'><div class='voteNR'>$lID</div><span>".$cData->Name."</span></li>";
    $i += 1;
}


echo "
            </ol>
        </div>
        <div id='ballotBox' class='lfloat shaded'>
            <h2>Röstsedel</h2>
            <ol class='ballotVoted'>
            </ol>
            <ul class='ballotUnvoted'>
            </ul>
            <div class='ballotNr'>RÖST #<span></span></div>
        </div>
        <div class='clear'> </div>
    </div>
    <div class='clear'> </div>
    <div id='voteControl'>
        <ul>
            <li><a id='voteButton' href='#' onClick='castVote()'><img class='key' src='./img/enter-key50.png' /><img class='icon' src='./img/political5.png' />Lägg röst</a></li>
            <li><a id='resetButton' href='#' onClick='resetVote()'><div class='key'>esc</div><img class='icon' src='./img/delete96.png' />Släng röst</a></li>
            <li><a id='resultLink' href='./calculateWinners.php?election=$election&display=true'><img class='icon' src='./img/position5.png' />Se resultat</a></li>
        </ul>
    </div>

</div>
";

?>
<script type='text/javascript' language='javascript'>

var originalList = getList();
var confirmState = false;

function getList()
{
    return $('#listOfRunners').html();
}

$('#listOfRunners').on('click', 'li', function(){
    addCandidateToBallot(this);
});



function addCandidateToBallot(liElement)
{
    var candidateID = $(liElement).attr("data-cID");
    var Name = $(liElement).children("span").text();
    if($('#ballotBox .ballotVoted li[data-cID='+candidateID+']').length)
        {
            // Perhaps some more elequate error handling?
            alert('En röst har redan lagts på den här personen.');
        }
    else
    {
        $(liElement).remove();
        $('#ballotBox .ballotVoted').append("<li data-cID='" + candidateID + "'>" + Name + "</li>");
    }
    return true;
}

$('#removeBallotsLink').click(function(){
    $('#delConfirm').show();
});
$('#delConfirmNo').click(function(){
    $('#delConfirm').hide();
});
$('#delConfirmYes').click(function(){
    $.ajax(
    {
        url: './ajax/removeBallots.php',
        data: { election: <? echo $election; ?> },
        datatype: 'xml',
        type: 'POST',
        cache: false,
        async: false,
        success: function(xml)
        {
            var status = $(xml).find("status").text();
            if(status == "OK")
            {
                location.reload();                               
            }
        }
    });
    $('#delConfirm').hide();
});

$(document).keyup(function (e) {
    if(confirmState == true)
    {
        if (e.which == 13) {
            confirmVote();
        }
        if (e.which == 27) {
            cancelCastingVote();
        }
    }
    else
    {
        if (e.which == 27) {
            resetVote();
        }
        if (e.which == 13) {
            castVote();
        }
        if (e.which > 48 && e.which < 57) {
            var acID = e.which-48;
            if($('#listOfRunners li[data-listID='+ acID +']').length) {
                addCandidateToBallot($('#listOfRunners li[data-listID=' + acID + ']'));    
            }
        }

    }
});

function resetVote()
{
    emptyBallot();
    resetRunnerList();
}
function confirmVote()
{
    var cIDs = new Array();
    var ballotNr = $('#confirmBallot .ballotNr span').text()
    var i = 1;
    $('#confirmBallot .ballotVoted li').each(function(){
        var cID = $(this).attr("data-cID");
        cIDs[i] = cID;
        i++;
    });
    if(voteToDB(cIDs))
    {
        $('#confirmWrap').remove();
        $('#shadow').remove();
        confirmState = false;
        resetVote();
        getBallotNr();
        getVotes();
    }
    else
        alert("Misslyckades med att lägga till rösten!");
}

function voteToDB(cIDs, ballotNr)
{
    var addDone = false;
    result = null;
    $.ajax(
    {
        url: './ajax/addBallot.php',
        data: { Election: <? echo $election; ?>, ballotNr: ballotNr  },
        datatype: 'xml',
        type: 'POST',
        cache: false,
        async: false,
        success: function(xml)
        {
            var status = $(xml).find("status").text();
            if(status == "OK")
            {
                var ballotID = $(xml).find("BallotID").text();
                var j = 0;
                if(cIDs.length == 0)
                    result = true;
                else
                {
                    for (var i = 0; i < cIDs.length; i++) {
                        $.ajax({
                            url: './ajax/castVote.php',
                            data: { Ballot: ballotID, Candidate: cIDs[i], Rank: i },
                            type: 'POST',
                            cache: false,
                            async: false,
                            success: function(xml){
                                var status = $(xml).find("status").text();
                                if(status == "OK")
                                {
                                    j++;
                                }
                            }
                        });
                    }
                    if(j == cIDs.length-1)
                        result = true;
                    else
                        result = false;                    
                }
            }
        }
    });
    return result;
}
function getBallotNr()
{
    var getElection = $.post('./ajax/getNextBallotNr.php', { Election: <? echo $election ?> });
    getElection.done(function(xml){
        var status = $(xml).find("status").text();
        if(status == "OK")
        {
            var ballotNr = $(xml).find("ballotNr").text();
            $('.ballotNr span').text(ballotNr);
        }
        else
            alert("Kunde inte hämta röstens nummer");
    });
}

function castVote()
{
    $('body').append("<div id='shadow'></div>" +
        "<div id='confirmWrap'>" +
        "<div id='confirmBallot'><a id='closeDiv' onClick='cancelCastingVote()'>x</a></div></div>");
    $('#confirmBallot').append($('#ballotBox').html());
    $('#confirmBallot .ballotUnvoted').append($('#listOfRunners').html());
    $('#confirmBallot div.voteNR').each(function(){
        $(this).remove();
    });
    confirmState = true;
}
function cancelCastingVote()
{
    $('#confirmWrap').remove();
    $('#shadow').remove();
    confirmState = false;
}

function emptyBallot()
{
    $('#ballotBox .ballotVoted').empty();
}
function resetRunnerList()
{
    $('#listOfRunners').empty();
    $('#listOfRunners').append(originalList);
    return true;
}
function resetBallot()
{
    replaceList(originalList);
}

function addResultBoxes()
{
    $('#listOfRunners li').each(function(){
        var cID = $(this).attr("data-cID");
        $('#resultContainer ul').append("<li class='candidateListElement' data-cID='" + cID + "'><div><h2>" + $(this).find("span").html() + "</h2><ol></ol></div></li>");
    });
}
function resizeResultBoxes()
{
    var minHeight = 150;
    var rowHeight = 58 + 16 * ($('#resultContainer li.candidateListElement').length - 1);
    rowHeight = Math.max(rowHeight, minHeight)
    var minLiWidth = 120;
    var maxLiWidth = 180;
    var liWidth = 110; // initializing with value belove min value.
    var rows = 0;
    var ulWidth = $('#resultContainer ul').width();
    var liCount = $('#resultContainer li.candidateListElement').length;
    while(liWidth<minLiWidth)
    {
        rows++;
        
        liWidth = ulWidth/(Math.ceil(liCount/rows))-5;
    }
    liWidth = Math.min(liWidth, maxLiWidth);
    $('#resultContainer').css("height", rows*rowHeight);
    $('#container').css("margin-bottom", rows*rowHeight+20);

    $('.candidateListElement').css("height", Math.round(100/rows)+"%");
    $('#resultContainer li.candidateListElement').css("width", liWidth);
}
// function moveVoteButtons()
// {
//     var numberOfRunners = $('#listOfRunners li').length;
//     var topOffset = 131 + 26 * numberOfRunners + 15;
//     var buttonBox = $('#voteButtonBox');
//     var resultButton = $('#resultLink');
//     var buttonHeight = parseInt($(buttonBox).css('height').match(/\d+/g));
//     var resHeight = parseInt($(resultButton).css('height').match(/\d+/g));

//     if(topOffset<300)
//     {
//         console.log('Försöker flytta knapparna till '+ topOffset);
//         $(buttonBox).css('top',topOffset);
//         var resTopOffset = topOffset + (buttonHeight - resHeight);
//         $(resultButton).css('top', resTopOffset);
//     }
//     else
//     {
//         var resTopOffset = (35 + buttonHeight);
//         console.log(resTopOffset);
//         $(buttonBox).css('top',5).css('right',-270);
//         $(resultButton).css('top', resTopOffset).css('left', 590);
//     }
// }
function getVotes()
{
    $('.updated').removeClass('updated');
    $('#resultContainer li.candidateListElement').each(function(){
        var cID = $(this).attr("data-cID");
        var eID = <? echo $election; ?>;
        
        var displayLIs = $(this).find("ol li");
        var displayCount = displayLIs.length;

        var allCounts = $.post('./ajax/getVote.php', { Election: eID, Candidate: cID });
        allCounts.done(function(xml){
            var status = $(xml).find("status").text();
            if(status == "OK")
            {
                var resultCount = $(xml).find("result").length;
                if(displayCount == resultCount)
                {
                    $(xml).find("result").each(function(){
                        var rank = $(this).find("rank").text();
                        var voteC = $(this).find("voteCount").text();
                        var liNum = rank-1;
                        var liText = $('#resultContainer li[data-cID=' + cID + '] ol li:eq(' + liNum + ')').text();
                        if(liText != dashForZero(voteC))
                        {
                            $('#resultContainer li[data-cID=' + cID + '] ol li:eq(' + liNum + ')').text(dashForZero(voteC));
                            $('#resultContainer li[data-cID=' + cID + '] ol li:eq(' + liNum + ')').addClass("updated");
                        }
                    });
                }
                else
                {
                    $('#resultContainer li[data-cID=' + cID + '] ol').empty();
                    var i = 1;
                    $(xml).find("result").each(function(){
                        var rank = $(this).find("rank").text();
                        if(rank == i)
                        {
                            var voteC = $(this).find("voteCount").text();
                            $('#resultContainer li[data-cID=' + cID + '] ol').append("<li>" + dashForZero(voteC) + "</li>");
                        }
                        else
                            alert("Något har blivit allvarligt fel när röstningsresultaten hämtades!");
                        i++;
                    });
                }
            }
        });
    });
}
function dashForZero(num)
{
    if(num == 0)
        return "-";
    else
        return num;
}
$(document).ready(function(){
    getBallotNr();
    addResultBoxes();
    resizeResultBoxes();
    getVotes();
    $('#voteControl').slideDown("slow");
});
$(window).resize(function(){
    resizeResultBoxes();
});

</script>
<?

echo "</div>
<div id='resultContainer'><ul></ul></div>
</body>
</html>
";
?>