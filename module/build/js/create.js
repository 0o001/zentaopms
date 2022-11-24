$().ready(function()
{
    $(document).on('click', '#lastBuildBtn', function()
    {
        $('#name').val($(this).text()).focus();
    });

    $(document).on('change', '#product, #branch', function()
    {
        var productID = $('#product').val();
        var branch    = $('#branch').length > 0 ? $('#branch').val() : '';
        $.get(createLink('build', 'ajaxGetProjectBuilds', 'projectID=' + projectID + '&productID=' + productID + '&varName=builds&build=&branch=' + branch + '&index=&needCreate=&type=noempty,notrunk,separate,singled&extra=multiple'), function(data)
        {
            if(data) $('#buildBox').html(data);
            $('#builds').attr('data-placeholder', multipleSelect).chosen();
        });
    });

    $('input[name=isIntegrated]').change(function()
    {
        if($(this).val() == 'no')
        {
            $('#execution').closest('tr').show();
            $('#buildBox').closest('tr').hide();
            loadProducts($('#execution').val());
        }
        else
        {
            $('#execution').closest('tr').hide();
            $('#buildBox').closest('tr').show();
            loadProducts($('#project').val());
        }
    });
    $('#product').change();
});

/**
 * Load products.
 *
 * @param  int $executionID
 * @access public
 * @return void
 */
function loadProducts(executionID)
{
    $('#product').remove();
    $('#product_chosen').remove();
    $('#branch').remove();
    $('#branch_chosen').remove();
    $('#noProduct').remove();
    $.get(createLink('product', 'ajaxGetProducts', 'executionID=' + executionID), function(data)
    {
        if(data)
        {
            if(data.indexOf("required") != -1)
            {
                $('#productBox').addClass('required');
            }
            else
            {
                $('#productBox').removeClass('required');
            }

            $('#productBox').append(data);
            $('#product').chosen();
            loadBranches($("#product").val());
        }
    });
    loadLastBuild();
}

function loadLastBuild()
{
   var executionID = $('#execution').val();
   $.get(createLink('build', 'ajaxGetLastBuild', 'projectID=' + projectID + '&executionID=' + executionID), function(data)
   {
       if(data) $('#lastBuildBox').html(data);
   });
}
