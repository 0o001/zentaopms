$(function()
{
    if(typeof(resetActive) != 'undefined') return false;
    if(typeof(storyType) == 'undefined') storyType = '';
    if(typeof(rawModule) == 'undefined') rawModule = 'product';
    if(typeof(app)       == 'undefined') app       = '';
    if(typeof(execution) != 'undefined') rawModule = 'projectstory';
    if(['project', 'projectstory'].indexOf(rawModule) === -1 && app != 'qa')
    {
        if(app != 'my') $('#navbar .nav li[data-id!=' + storyType + ']').removeClass('active');
        $("#navbar .nav li[data-id=" + storyType + ']').addClass('active');
        $('#subNavbar li[data-id="' + storyType + '"]').addClass('active');
        if($('#navbar .nav>li[data-id=story] .dropdown-menu').length) $('#navbar .nav>li[data-id=story]>a').html($('.active [data-id=' + storyType + ']').text() + '<span class="caret"></span>');
    }

    $('#saveButton').on('click', function()
    {
        $('#saveButton').attr('disabled', true);
        $('#saveDraftButton').attr('disabled', true);

        var storyStatus = !$('#reviewer').val() || $('#needNotReview').is(':checked') ? 'active' : 'reviewing';
        $('<input />').attr('type', 'hidden').attr('name', 'status').attr('value', storyStatus).appendTo('#dataform');
        $('#dataform').submit();

        setTimeout(function()
        {
            $('#saveButton').removeAttr('disabled');
            $('#saveDraftButton').removeAttr('disabled');
        }, 1000);
    });

    $('#saveDraftButton').on('click', function()
    {
        $('#saveButton').attr('disabled', true);
        $('#saveDraftButton').attr('disabled', true);

        storyStatus = 'draft';
        if(typeof(page) != 'undefined' && page == 'change') storyStatus = 'changing';
        if(typeof(page) !== 'undefined' && page == 'edit' && $('#status').val() == 'changing') storyStatus = 'changing';
        $('<input />').attr('type', 'hidden').attr('name', 'status').attr('value', storyStatus).appendTo('#dataform');
        $('#dataform').submit();

        setTimeout(function()
        {
            $('#saveButton').removeAttr('disabled');
            $('#saveDraftButton').removeAttr('disabled');
        }, 1000);
    });
})

/**
 * Get status.
 *
 * @param  method $method
 * @param  params $params
 * @access public
 * @return void
 */
function getStatus(method, params)
{
    $.get(createLink('story', 'ajaxGetStatus', "method=" + method + '&params=' + params), function(status)
    {
        $('form #status').val(status).change();
    });
}
