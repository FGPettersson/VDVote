<?

require_once('./functions-mysql.php');
require_once('./functions-other.php');

$sql_connection = connectToDatabase();
print_html_header("<link rel='stylesheet' href='css/electionstyle.css'>");  
$election = isset($_GET['election'])?$_GET['election']:null;
$title = "Inget val är valt";
if(isset($election))
{
    $electionRes = $sql_connection->query("SELECT * FROM elections WHERE id=$election");
    if($electionRes->num_rows == 1)
    {
        $electionData = $electionRes->fetch_object();
        $title = $electionData->Position;
    }
    else
    {
        $electionFail = true;
        $title = "Kunde inte hitta valt val";        
    }

}


echo "

    <div class='centerBox'>
        <div class='myList'>
            <h2>Kandidater</h2>
            <input type='text' id='newCandidate' placeholder='Lägg till ny kandidat' />
            <ul id='runnerList' class='sortable'>
            </ul>
        </div>
        <div id='electionInfo'>
            <label><input type='text' id='ePosition' class='eForm' /><span>Position</span><img class='inputRightImage' src='./img/business133.png'></label>
            <label><textarea id='eDescription' class='eForm'></textarea><span id='eDescSpan'>Beskrivning</span><img class='textareaRightImage' src='./img/write12.png'></label>
            <div id='numberOfSeatsAndBackups'>
                <label><input id='eNumberOfSeats' type='text' class='shortInput eForm' /><span>Antal platser</span><img src='./img/chair.png' /></label>
                <label><input id='eNumberOfBackups' type='text' class='shortInput eForm' disabled /><span>Antal suppleanter</span></label>
            </div>
            <div id='majorityInput'>
                <label>
                    <input id='eMajorityNeeded' type='text' class='shortInput eForm' disabled />
                    <span>Majoritetskrav</span>
                    <img src='./img/pie46.png' />
                </label>
                <!-- <label><input type='checkbox' id='e-rankedSeats' />Rangordnade platser</label> --!>
            </div>
            
            <a href='#' class='buttonStyle' id='updateLink'>Uppdatera valet</a>
            <div id='updateStatus' class='notUsed notUpdated'><img src='./img/alert9.png' /><span></span></div>
            </div>

        </div>
        <div class='clear'></div>
    </div>
</div>";

print_html_footer();
?>

<script type='text/javascript' language='javascript'>
var firstGet = true;

function getRunners()
{
    $.ajax({
        url: './ajax/getRunners.php',
        type: 'POST',
        data: { election: <? echo $election; ?> },
        cache: false,
        async: false,
        dataType: 'xml',
        success: function(xml){
            var runners = $(xml).find("runner").each(function(){
                var cID = $(this).find("CandidateID").text();
                var Name = $(this).find("Name").text();
                if($('#c_'+cID).length)
                    {}
                else
                {
                    if(firstGet == true)
                    {
                        $('<li id=c_' + cID + '><img src="./img/remove.png" onclick="removeRunner(' + cID + ')" /><span>' + Name + '</span></li>').appendTo('#runnerList');
                    }
                    else
                    {
                        $('<li id=c_' + cID + '><img src="./img/remove.png" onclick="removeRunner(' + cID + ')" /><span>' + Name + '</span></li>').hide().appendTo('#runnerList').slideDown("fast");
                    }

                }
            });
        }
    });
    firstGet = false;
    return false;
}
var formInfo = new Array();

function loadElection()
{
    getE = $.post('./ajax/getElection.php', { election: <? echo $election; ?> });
    getE.done(function(xml){
        formInfo['ePosition'] = $(xml).find("Position").text();
        formInfo['eDescription'] = $(xml).find("Details").text();
        formInfo['eNumberOfSeats'] = $(xml).find("NumberOfSeats").text();
        formInfo['eNumberOfBackups'] = $(xml).find("NumberOfBackups").text();
        formInfo['eMajorityNeeded'] = Math.round($(xml).find("MajorityNeeded").text())+'%'
        $('#ePosition').val(formInfo['ePosition']);
        $('#eDescription').val(formInfo['eDescription']);
        $('#eNumberOfSeats').val(formInfo['eNumberOfSeats']);
        $('#eNumberOfBackups').val(formInfo['eNumberOfBackups']);
        $('#eMajorityNeeded').val(formInfo['eMajorityNeeded']);

        var uS = $('#updateStatus');
        console.log(uS);
        if(uS.hasClass('notUpdated'))
        {
            $('#updateStatus').removeClass('notUpdated').addClass('updateOK').find('span').text("Info är sparad och uppdaterad.");
            $('#updateStatus').find('img').attr('src', './img/ok2.png');
        }
    });
}

$('.eForm').change(function(){
    var oldVal = formInfo[$(this).attr('id')];
    var newVal = $(this).val();

    if (oldVal != newVal && $('#updateStatus').hasClass('updateOK'))
    {
        $('#updateStatus').removeClass('updateOK').removeClass('notUsed').addClass('notUpdated').find('span').text("Behöver uppdateras!");
        $('#updateStatus').find('img').attr('src', './img/alert9.png');
    }
        
});

function updateElection()
{
    uppE = $.post('./ajax/editElection.php', 
        { 
            ElectionId: <? echo $election; ?>,
            Position: $('#ePosition').val(),
            Details: $('#eDescription').val(),
            NumberOfSeats: $('#eNumberOfSeats').val(),
            NumberOfBackups: $('#eNumberOfBackups').val(),
            RankedSeats: 0,
            MajorityNeeded: $('#eMajorityNeeded').val().replace(/\D/g,'')
        });
    uppE.done(function(xml){
        console.log(xml);
        if($(xml).find('status').text() == "OK")
        {
            loadElection();
        }
        else
        {
            console.log($(xml).find('status').text());
        }
    });
}
$('#updateLink').click(function(){
    updateElection();
});

function removeElection(){
    var electionID = <? echo $election ?>;
    remC = $.post('./ajax/removeElection.php', { election: electionID });
    remC.done(function(xml){
        if($(xml).find("status").text() == "OK")
            location.href = './index.php';
        else
            alert("Kunde inte ta bort valet\n" + $(xml).find("status").text());
    });
}

function removeRunner(cID)
{
    remC = $.post('./ajax/removeRunner.php', { Candidate: cID, Election: <? echo $election; ?> });
    remC.done(function(xml){
        var status = $(xml).find("status").text();
        if(status == "OK")
        {
            $('#c_' + cID + ' img').remove();
            $('#c_' + cID).remove();
            // $('#c_' + cID).hide("fast",function(){ $(this).remove(); });
        }
    });
    return false;
}

$('#newCandidate').keypress(function (e) {
    if (e.which == 13) {
        e.preventDefault();
        var eID = <? echo $election ?>;
        var Name = $('#newCandidate').val();
        var addC = $.post('./ajax/addCandidate.php', { Name: Name });
        addC.done(function(xml){
            var cID = $(xml).find("CandidateID").text();
            var addR = $.post('./ajax/runForElection.php', { Election: eID, Candidate: cID });
            addR.done(function(){
                getRunners();
            });
        });
        $('#newCandidate').val("");
        getRunners();
    }
});
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


$('.sortable').sortable();

$(document).ready(function(){
    getRunners();
    loadElection();
});

</script>

</html>