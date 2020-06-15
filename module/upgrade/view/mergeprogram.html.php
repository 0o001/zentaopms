<?php
/**
 * The mergeProgram view file of upgrade module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Yidong Wang <yidong@cnezsoft.com>
 * @package     upgrade
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include '../../common/view/header.lite.html.php';?>
<?php js::set('weekend', $config->project->weekend);?>
<div class='container'>
  <form method='post' target='hiddenwin'>
    <div class='modal-dialog'>
      <div class='modal-header'>
        <strong><?php echo $lang->upgrade->mergeProgram;?></strong>
      </div>
      <div class='modal-body'>
        <div class='alert alert-info'>
          <?php
          printf($lang->upgrade->mergeSummary, $noMergedProductCount, $noMergedProjectCount);
          if($type == 'productline') echo '<br />' . $lang->upgrade->mergeByProductLine;
          if($type == 'product')     echo '<br />' . $lang->upgrade->mergeByProduct;
          if($type == 'project')     echo '<br />' . $lang->upgrade->mergeByProject;
          if($type == 'moreLink')    echo '<br />' . $lang->upgrade->mergeByMoreLink;
          ?>
        </div>
        <?php if($type == 'productline'):?>
        <?php include './mergebyline.html.php';?>
        <?php elseif($type == 'product'):?>
        <?php include './mergebyproduct.html.php';?>
        <?php elseif($type == 'project'):?>
        <?php include './mergebyproject.html.php';?>
        <?php elseif($type == 'moreLink'):?>
        <table class='table table-form'>
          <thead>
            <tr>
              <th><?php echo $lang->upgrade->project;?></th>
              <th><?php echo $lang->upgrade->program;?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($noMergedProjects as $projectID => $project):?>
          <tr>
            <td><?php echo "{$lang->projectCommon} #{$project->id} {$project->name}" . html::hidden("projects[]", $project->id);?></td>
            <td><?php echo html::select("programs[]", $project->programs, '', "class='form-control chosen'");?></td>
          </tr>
          <?php endforeach;?>
          </tbody>
        </table>
        <?php endif;?>
      </div>
      <div class='modal-footer'><?php echo html::submitButton();?></div>
    </div>
  </form>
</div>
<?php include '../../common/view/footer.lite.html.php';?>
