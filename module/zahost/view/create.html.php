<?php
/**
 * The create view file of zahost module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Jianhua Wang<wangjianhua@easycorp.ltd>
 * @package     zahost
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include $app->getModuleRoot() . 'common/view/header.html.php';?>
<div id='mainContent' class='main-content'>
  <div class='main-header'>
    <h2><?php echo $lang->zahost->create?></h2>
  </div>
  <form method='post' target='hiddenwin' id='ajaxForm' class="load-indicator main-form form-ajax">
    <table class='table table-form'>
      <tr>
        <th class='w-150px'><?php echo $lang->zahost->name;?></th>
        <td><?php echo html::input('name', '', "class='form-control'");?></td>
        <td></td>
      </tr>
      <tr>
        <th><?php echo $lang->zahost->zaHostType;?></th>
        <td><?php echo html::select('hostType', $lang->zahost->zaHostTypeList, 'virtual', "class='form-control chosen'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->zahost->IP;?></th>
        <td><?php echo html::input('publicIP', '', "class='form-control'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->zahost->cpuCores;?></th>
        <td><?php echo html::input('cpuCores', '', "class='form-control'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->zahost->memory;?></th>
        <td>
          <div class='input-group'>
            <?php echo html::input('memory', '', "class='form-control'");?>
            <span class="input-group-addon"><?php echo $lang->zahost->unitList['GB'];?></span>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->zahost->diskSize;?></th>
        <td>
          <div class='input-group'>
            <?php echo html::input('diskSize', '', "class='form-control'");?>
            <span class='input-group-addon fix-border fix-padding' id='unit'>
              <?php echo html::select('unit', $lang->zahost->unitList, 'GB', "class='form-control chosen w-50px'");?>
            </span>
          </div>
        </td>
      </tr>
      <tr>
        <th><?php echo $lang->zahost->virtualSoftware;?></th>
        <td><?php echo html::select('virtualSoftware', $lang->zahost->softwareList, '', "class='form-control chosen'");?></td>
      </tr>
      <tr>
        <th><?php echo $lang->zahost->instanceNum?></th>
        <td><?php echo html::input('instanceNum', '', "class='form-control'");?></td>
      </tr>
      <tr>
        <td colspan='2' class='text-center form-actions'>
          <?php echo html::submitButton();?>
          <?php echo html::backButton();?>
          <?php echo html::hidden('type', 'za');?>
        </td>
      </tr>
    </table>
  </form>
</div>
<?php include $app->getModuleRoot() . 'common/view/footer.html.php';?>
