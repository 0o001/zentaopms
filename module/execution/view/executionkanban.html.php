<?php
/**
 * The execution kanban view file of execution module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2021 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @author      Qiyu Xie
 * @package     execution
 * @version     $Id: executionkanban.html.php $
 */
?>
<?php include '../../common/view/header.html.php';?>
<div id="kanban" class="main-table fade auto-fade-in" data-ride="table" data-checkable="false" data-group="true">
  <?php if(empty($kanbanGroup)):?>
  <div class="table-empty-tip">
    <p>
      <span class="text-muted"><?php echo $lang->execution->noExecutions;?></span>
    </p>
  </div>
  <?php else:?>
  <table class="table no-margin table-grouped text-center">
    <thead>
      <tr>
        <th class='projectColor'></th>
        <th><?php echo $lang->execution->doingProject . ' <span class="count">' . $projectCount . '</span>';?></th>
        <?php foreach($lang->execution->kanbanColType as $status => $colName):?>
        <th><?php echo $colName . ($status != 'closed' ? ' <span class="count">' . $statusCount[$status] . '</span>' : '');?></th>
        <?php endforeach;?>
      </tr>
    </thead>
    <tbody>
      <?php $rowIndex = 0;?>
      <?php foreach($kanbanGroup as $projectID => $executionList):?>
      <tr>
        <td style="background: <?php echo $lang->execution->boardColorList[$rowIndex];?>;"></td>
        <td class='board-project'>
          <div data-id='<?php echo $projectID;?>'>
            <div class='text-center'>
              <?php $projectTitle = empty($projectID) ? $lang->execution->myExecutions : zget($projects, $projectID);?>
              <span class='group-title' title='<?php echo $projectTitle;?>'><?php echo $projectTitle;?></span>
            </div>
          </div>
        </td>
        <td class='c-boards no-padding text-left' colspan='4'>
          <div class="boards-wrapper">
            <div class="boards">
              <?php foreach($lang->execution->kanbanColType as $colStatus => $colName):?>
              <div class="board s-<?php echo $colStatus?>">
                <div>
                  <?php if(!empty($executionList[$colStatus])):?>
                  <?php foreach($executionList[$colStatus] as $execution):?>
                  <div class='board-item' <?php if($execution->status == 'doing' and isset($execution->delay)) echo "style='border-left: 3px solid red';";?>>
                    <div class='table-row'>
                      <div class='table-col'>
                        <?php
                        $executionName = empty($projectID) ? zget($projects, $execution->project) . ' / ' . $execution->name : $execution->name;

                        if(common::hasPriv('execution', 'task'))
                        {
                            echo html::a($this->createLink('execution', 'task', "executionID=$execution->id"), $executionName, '', "title='{$executionName}'");
                        }
                        else
                        {
                            echo "<span title='{$executionName}'>{$executionName}</span>";
                        }
                        ?>
                      </div>
                      <?php if($colStatus == 'doing'):?>
                      <div class='table-col'>
                        <div class="c-progress">
                          <div class='progress-pie' data-doughnut-size='90' data-color='#3CB371' data-value='<?php echo round($execution->hours->progress);?>' data-width='24' data-height='24' data-back-color='#e8edf3'>
                            <div class='progress-info'><?php echo round($execution->hours->progress);?></div>
                          </div>
                        </div>
                      </div>
                      <?php endif?>
                    </div>
                  </div>
                  <?php endforeach?>
                  <?php endif?>
                </div>
              </div>
              <?php endforeach;?>
            </div>
          </div>
        </td>
      </tr>
      <?php $rowIndex++; ?>
      <?php if($rowIndex == 8) $rowIndex = 0;?>
      <?php endforeach;?>
    </tbody>
  </table>
  <?php endif;?>
</div>
<style>
<?php
foreach(array_keys($lang->execution->kanbanColType) as $status)
{
    echo ".s-$status .board-item {border-left: 3px solid {$lang->execution->statusColorList[$status]};}";
}
?>
</style>
<?php include '../../common/view/footer.html.php';?>
