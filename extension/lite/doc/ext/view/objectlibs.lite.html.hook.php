<?php if($this->app->tab == 'project'):?>
<script>
$('#pageNav .btn-group #dropMenu .table-col .list-group a[href*="showFiles"]').remove();
$('#pageActions .btn-toolbar .btn-group:first').remove();
</script>
<?php endif;?>
