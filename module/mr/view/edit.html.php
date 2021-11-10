<?php
/**
 * The edit view file of mr module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2012 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @author      Wang Yidong, Zhu Jinyong
 * @package     mr
 * @version     $Id: create.html.php $
 */
?>
<?php include '../../common/view/header.html.php';?>
<?php js::set('gitlabID', $MR->gitlabID);?>
<?php js::set('projectID', $MR->sourceProject);?>
<div id='mainContent' class='main-row'>
  <div class='main-col main-content'>
    <div class='center-block'>
      <div class='main-header'>
        <h2><?php echo $lang->mr->edit;?></h2>
      </div>
      <form id='mrForm' method='post' class='form-ajax'>
        <table class='table table-form'>
          <tr>
            <th><?php echo $lang->gitlab->common;?></th>
            <td><?php echo $this->loadModel('gitlab')->getByID($MR->gitlabID)->name;?></td>
          </tr>
          <tr>
             <th style="white-space: nowrap;"><?php echo $lang->mr->sourceProject;?></th>
             <td>
               <div>
                 <span class='fix-border text-left'>
                 <?php echo $this->loadModel('gitlab')->apiGetSingleProject($MR->gitlabID, $MR->sourceProject)->name_with_namespace; ?>:
                 <?php echo $MR->sourceBranch;?>
                 </span>
               </div>
             </td>
          </tr>
          <tr>
             <th style="white-space: nowrap;"><?php echo $lang->mr->targetProject;?></th>
             <td>
               <div class='input-group'>
                 <span class='input-group-addon fix-border'>
                 <?php echo $this->loadModel('gitlab')->apiGetSingleProject($MR->gitlabID, $MR->targetProject)->name_with_namespace;?>
                 </span>
                 <?php echo html::select('targetBranch', $targetBranchList, $MR->targetBranch, "class='form-control chosen'");?>
               </div>
             </td>
          </tr>
          <tr>
            <th><?php echo $lang->mr->title;?></th>
            <td class='required'><?php echo html::input('title', $MR->title, "class='form-control'"); ?></td>
          </tr>
          <tr>
            <th><?php echo $lang->mr->description; ?></th>
            <td colspan='1'><?php echo html::textarea('description', $MR->description, "rows='3' class='form-control'"); ?></td>
          </tr>
          <tr>
            <th><?php echo $lang->mr->needPassCI; ?></th>
            <td colspan='1'>
              <div class="checkbox-primary">
                <?php $checked = $MR->needPassCI == '1' ? 'checked' : '' ?>
                <input type="checkbox" <?php echo $checked; ?> name="needPassCI" value="1" id="needPassCI">
                <label for="needPassCI"></label>
              </div>
            </td>
          </tr>
          <tr>
            <th><?php echo $lang->devops->repo; ?></th>
            <td colspan='1' class='required'><?php echo html::select('repo', $repoList, $MR->repoID, "class='form-control chosen'"); ?></td>
          </tr>
          <tr>
            <th><?php echo $lang->job->common; ?></th>
            <td colspan='1'><?php echo html::select('job', $jobList, $MR->jobID, "class='form-control chosen'"); ?></td>
          </tr>
          <tr>
            <th><?php echo $lang->compile->result; ?></th>
            <td colspan='1'><?php echo html::select('compile', $compileList, $MR->compileID, "class='form-control chosen'"); ?></td>
          </tr>
          <tr>
            <th><?php echo $lang->mr->assignee;?></th>
            <td><?php echo html::select('assignee', $users, $assignee, "class='form-control chosen'")?></td>
          </tr>
          <tr>
            <th></th>
            <td><?php echo $lang->mr->usersTips;?></td>
          </tr>
          <tr>
            <td colspan='2' class='text-center form-actions'>
              <?php echo html::submitButton(); ?>
              <?php if(!isonlybody()) echo html::a(inlink('browse', ""), $lang->goback, '', 'class="btn btn-wide"');?>
            </td>
            <th></th>
          </tr>
        </table>
      </form>
    </div>
  </div>
</div>
<?php include '../../common/view/footer.html.php'; ?>
