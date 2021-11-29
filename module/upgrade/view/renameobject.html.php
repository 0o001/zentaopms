<?php include '../../common/view/header.lite.html.php';?>
<style>
.group-end {border-bottom: 1px solid #efefef;}
</style>
<div id='mainContent' class='main-content'>
  <div class='center-block'>
    <div class='main-header'>
      <h2>
        <?php if($type == 'project') echo $lang->upgrade->duplicateProject;?>
      </h2>
    </div>
    <form method='post' enctype='multipart/form-data' target='hiddenwin'>
      <table class='table table-form'>
        <tr>
          <th class='w-40px text-left'><?php echo $lang->$type->id;?></th>
          <th class='c-name text-left'><?php echo $lang->$type->name;?></th>
          <th class='c-name text-left'><?php echo $lang->upgrade->editedName;?></th>
        </tr>
        <?php foreach($objectGroup as $objectName => $objectList):?>
        <?php foreach($objectList as $key => $object):?>
        <tr <?php if($object->id == end($objectList)->id) echo "class='group-end'";?>>
          <td><?php echo $object->id;?></td>
          <td><?php echo $object->name;?></td>
          <td><?php echo html::input("project[$object->id]", '', "class='form-control'");?></td>
        </tr>
        <?php endforeach;?>
        <?php endforeach;?>
        <tr>
          <td colspan='3' class='text-center form-actions'><?php echo html::submitButton();?></td>
        </tr>
      </table>
    </form>
  </div>
</div>
<?php include '../../common/view/footer.lite.html.php';?>
