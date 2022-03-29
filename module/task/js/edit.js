$(function()
{
    $('.record-estimate-toggle').modalTrigger({width:900, type:'iframe', afterHide: function(){parent.location.href=parent.location.href;}});
})

/**
 * Load module, stories and members.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadAll(executionID)
{
    if(!changeExecutionConfirmed)
    {
        firstChoice = confirm(confirmChangeExecution);
        changeExecutionConfirmed = true;    // Only notice the user one time.
    }
    if(changeExecutionConfirmed && firstChoice)
    {
        loadModuleMenu(executionID);
        loadExecutionStories(executionID);
        loadExecutionMembers(executionID);
    }
    else
    {
        $('#execution').val(oldExecutionID);
        $("#execution").trigger("chosen:updated");
    }
}

/**
 * Load module of the execution.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadModuleMenu(executionID)
{
    var link = createLink('tree', 'ajaxGetOptionMenu', 'rootID=' + executionID + '&viewtype=task');
    $('#moduleIdBox').load(link, function(){$('#module').chosen();});
}

/**
 * Load stories of the execution.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadExecutionStories(executionID, moduleID)
{
    if(typeof(moduleID) == 'undefined') moduleID = 0;
    var link = createLink('story', 'ajaxGetExecutionStories', 'executionID=' + executionID + '&productID=0&branch=0&moduleID=' + moduleID + '&storyID=' + oldStoryID);
    $('#storyIdBox').load(link, function(){$('#story').chosen();});
}

/**
 * Load team members of the execution.
 *
 * @param  int    $executionID
 * @access public
 * @return void
 */
function loadExecutionMembers(executionID)
{
    var link = createLink('execution', 'ajaxGetMembers', 'executionID=' + executionID + '&assignedTo=' + oldAssignedTo);
    $('#assignedToIdBox').load(link, function(){$('#assignedToIdBox').find('select').chosen()});
}

/**
 * Load module related
 *
 * @access public
 * @return void
 */
function loadModuleRelated()
{
    moduleID    = $('#module').val();
    executionID = $('#execution').val();
    loadExecutionStories(executionID, moduleID)
}

/* empty function. */
function setPreview(){}

$(document).ready(function()
{
    /* show team menu. */
    $('[name=multiple]').change(function()
    {
        var checked = $(this).prop('checked');
        if(checked)
        {
            $('#teamTr').removeClass('hidden');
            $('#parent').val('');
            $('#parent').trigger('chosen:updated');
            $('#parent').closest('tr').addClass('hidden');
            $('#estimate').attr('disabled', 'disabled');
        }
        else
        {
            $('#teamTr').addClass('hidden');
            $('#parent').closest('tr').removeClass('hidden');
            $('#estimate').removeAttr('disabled');
        }
    });

    /* Init task team manage dialog */
    var $taskTeamEditor = $('#taskTeamEditor').batchActionForm(
    {
        idStart: 0,
        idEnd: newRowCount - 1,
        chosen: true,
        datetimepicker: false,
        colorPicker: false,
    });
    var taskTeamEditor = $taskTeamEditor.data('zui.batchActionForm');

    var adjustButtons = function()
    {
        var $deleteBtn = $taskTeamEditor.find('.btn-delete');
        if ($deleteBtn.length == 1) $deleteBtn.addClass('disabled').attr('disabled', 'disabled');
    };

    $taskTeamEditor.on('click', '.btn-add', function()
    {
        var $newRow = taskTeamEditor.createRow(null, $(this).closest('tr'));
        $newRow.addClass('highlight');
        setTimeout(function()
        {
            $newRow.removeClass('highlight');
        }, 1600);
        adjustButtons();
    }).on('click', '.btn-delete', function()
    {
        var $row = $(this).closest('tr');
        $row.addClass('highlight').fadeOut(700, function()
        {
            $row.remove();
            adjustButtons();
        });
    });

    adjustButtons();

    $('#showAllModule').change(function()
    {
        var moduleID = $('#moduleIdBox #module').val();
        var extra    = $(this).prop('checked') ? 'allModule' : '';
        $('#moduleIdBox').load(createLink('tree', 'ajaxGetOptionMenu', "rootID=" + executionID + '&viewType=task&branch=0&rootModuleID=0&returnType=html&fieldID=&needManage=0&extra=' + extra), function()
        {
            $('#moduleIdBox #module').val(moduleID).attr('onchange', "loadModuleRelated()").chosen();
        });
    });
});

$('#confirmButton').click(function()
{
    /* Unique team. */
    $('select[name^=team]').each(function(i)
    {
        value = $(this).val();
        if(value == '') return;
        $('select[name^=team]').each(function(j)
        {
            if(i <= j) return;
            if(value == $(this).val()) $(this).closest('tr').addClass('hidden');
        })
    })
    $('select[name^=team]').closest('tr.hidden').remove();

    var memberCount   = '';
    var assignedTo    = '';
    var totalEstimate = 0;
    var totalConsumed = 0;
    var totalLeft     = 0;
    $('select[name^=team]').each(function()
    {
        if($(this).find('option:selected').text() == '') return;

        var account  = $(this).find('option:selected').val();
        var realName = $(this).find('option:selected').text();
        assignedTo += "<option value='" + account + "' title='" + realName + "'>" + realName + "</option>";
        memberCount++;

        estimate = parseFloat($(this).parents('td').next('td').find('[name^=teamEstimate]').val());
        if(!isNaN(estimate)) totalEstimate += estimate;

        consumed = parseFloat($(this).parents('td').next('td').find('[name^=teamConsumed]').val());
        if(!isNaN(consumed)) totalConsumed += consumed;

        left = parseFloat($(this).parents('td').next('td').find('[name^=teamLeft]').val());
        if(!isNaN(left)) totalLeft += left;
    })
    $('#estimate').val(totalEstimate);
    $('#consumedSpan').html(totalConsumed);
    $('#left').val(totalLeft);
    $('#assignedTo').html(assignedTo);
    $('#assignedTo').trigger('chosen:updated');

    if(memberCount < 2)
    {
        alert(teamMemberError);
    }
    else
    {
        $('.close').click();
    }
});
