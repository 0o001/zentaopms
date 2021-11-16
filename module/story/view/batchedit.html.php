<?php
/**
 * The batch edit view of story module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Congzhi Chen <congzhi@cnezsoft.com>
 * @package     story
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include '../../common/view/header.html.php';?>
<?php js::set('dittoNotice', $this->lang->story->dittoNotice);?>
<?php js::set('storyType', $storyType);?>
<?php js::set('app', $this->app->tab);?>
<?php if(isset($resetActive)) js::set('resetActive', true);?>
<div class='main-content' id='mainContent'>
<div class='main-header'>
  <h2>
    <?php echo $lang->story->common . $lang->colon . $lang->story->batchEdit;?>
  </h2>
  <div class='pull-right btn-toolbar'>
    <?php $customLink = $this->createLink('custom', 'ajaxSaveCustomFields', 'module=story&section=custom&key=batchEditFields')?>
    <?php include '../../common/view/customfield.html.php';?>
  </div>
</div>
<?php if(isset($suhosinInfo)):?>
<div id='suhosinInfo' class='alert alert-info'><?php echo $suhosinInfo;?></div>
<?php else:?>
<?php
$visibleFields = array();
foreach(explode(',', $showFields) as $field)
{
    if($storyType == 'requeirment' and $field == 'stage') continue;
    if($field) $visibleFields[$field] = '';
}
?>
<form method='post' target='hiddenwin' action="<?php echo inLink('batchEdit', "productID=$productID&executionID=$executionID")?>" id="batchEditForm">
  <div class="table-responsive">
    <table class='table table-form'>
      <thead>
        <tr>
          <th class='c-id'> <?php echo $lang->idAB;?></th>
          <?php if($branchProduct):?>
          <th class='c-branch<?php echo zget($visibleFields, 'branch', ' hidden')?>'><?php echo $lang->story->branch;?></th>
          <?php endif;?>
          <th class='c-module'><?php echo $lang->story->module;?></th>
          <th class='c-plan<?php echo zget($visibleFields, 'plan', ' hidden')?> col-plan'><?php echo $lang->story->planAB;?></th>
          <th class='c-title required'><?php echo $lang->story->title;?></th>
          <th class='c-estimate<?php echo zget($visibleFields, 'estimate', ' hidden')?>'> <?php echo $lang->story->estimateAB;?></th>
          <th class='c-category'><?php echo $lang->story->category;?></th>
          <th class='c-pri<?php echo zget($visibleFields, 'pri', ' hidden')?>'> <?php echo $lang->priAB;?></th>
          <th class='c-user<?php echo zget($visibleFields, 'assignedTo', ' hidden')?>'> <?php echo $lang->story->assignedTo;?></th>
          <th class='c-source<?php echo zget($visibleFields, 'source', ' hidden')?>'> <?php echo $lang->story->source;?></th>
          <th class='c-note<?php echo zget($visibleFields, 'source', ' hidden')?>'> <?php echo $lang->story->sourceNote;?></th>
          <th class='c-status'><?php echo $lang->story->status;?></th>
          <th class='c-stage<?php echo zget($visibleFields, 'stage', ' hidden')?>'> <?php echo $lang->story->stageAB;?></th>
          <th class='c-user-box<?php echo zget($visibleFields, 'closedBy', ' hidden')?>'><?php echo $lang->story->closedBy;?></th>
          <th class='c-reason<?php echo zget($visibleFields, 'closedReason', ' hidden')?>'> <?php echo $lang->story->closedReason;?></th>
          <th class='c-keywords<?php echo zget($visibleFields, 'keywords', ' hidden')?>'><?php echo $lang->story->keywords;?></th>
          <?php
          $extendFields = $this->story->getFlowExtendFields();
          foreach($extendFields as $extendField) echo "<th class='c-extend'>{$extendField->name}</th>";
          ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($stories as $storyID => $story):?>
        <?php
        if(!$productID)
        {
            $product = $this->product->getByID($story->product);

            $branches = $product->type == 'normal' ? array('' => '') : $this->loadModel('branch')->getPairs($product->id);
            if($product->type != 'normal')
            {
                foreach($branches as $branchID => $branchName) $branches[$branchID] = '/' . $product->name . '/' . $branchName;
            }

            if(!isset($modules[$story->branch])) $modules[$story->branch] = $this->tree->getOptionMenu($story->product, $viewType = 'story', 0, $story->branch);
            foreach($modules[$story->branch] as $moduleID => $moduleName) $modules[$story->branch][$moduleID] = '/' . $product->name . $moduleName;

            $productPlans = $this->productplan->getPairs($story->product, $branch);
        }
        ?>
        <tr>
          <td><?php echo $storyID . html::hidden("storyIdList[$storyID]", $storyID);?></td>
          <?php if($branchProduct):?>
          <td class='text-left<?php echo zget($visibleFields, 'branch', ' hidden')?>'>
            <?php $branchProductID = $productID ? $productID : $product->id;?>
            <?php $disabled        = (isset($product) and $product->type == 'normal') ? "disabled='disabled'" : '';?>
            <?php echo html::select("branches[$storyID]", $branches, $story->branch, "class='form-control chosen' onchange='loadBranches($branchProductID, this.value, $storyID);' $disabled");?>
          </td>
          <?php endif;?>
          <td class='text-left<?php echo zget($visibleFields, 'module')?>'>
            <?php echo html::select("modules[$storyID]", $modules[$story->branch], $story->module, "class='form-control chosen'");?>
          </td>
          <td class='text-left<?php echo zget($visibleFields, 'plan', ' hidden')?>'>
            <?php
            if($productID)
            {
                $productPlans = array('' => '');
                if($story->branch != BRANCH_MAIN)
                {
                    if(isset($plans[$story->branch])) $productPlans += $plans[$story->branch];
                    if(isset($plans[0])) $productPlans += $plans[0];
                }
                else
                {
                    foreach($plans as $branchPlan) $productPlans += $branchPlan;
                }
            }
            ?>
            <?php if($this->session->currentProductType == 'normal') $productPlans = array('' => '', 'ditto' => $this->lang->story->ditto) + $productPlans;?>
            <?php echo html::select("plans[$storyID]", $productPlans, $story->plan, "class='form-control chosen'");?>
          </td>
          <td title='<?php echo $story->title?>'>
            <div class="input-group">
              <div class="input-control has-icon-right">
                <?php echo html::input("", $story->title, "class='form-control input-story-title' disabled"); ?>
                <?php echo html::hidden("titles[$storyID]", $story->title); ?>

                <div class="colorpicker">
                  <button type="button" class="btn btn-link dropdown-toggle" data-toggle="dropdown"><span class="cp-title"></span><span class="color-bar"></span><i class="ic"></i></button>
                  <ul class="dropdown-menu clearfix">
                    <li class="heading"><?php echo $lang->story->colorTag;?><i class="icon icon-close"></i></li>
                  </ul>
                  <?php echo html::hidden("colors[$storyID]", $story->color, "class='colorpicker' data-wrapper='input-control-icon-right' data-icon='color' data-btn-tip='{$lang->story->colorTag}' data-update-color='#titles\\[{$storyID}\\]'");?>
                </div>
              </div>
            </div>
          </td>

          <td <?php echo zget($visibleFields, 'estimate', "class='hidden'")?>><?php echo html::input("estimates[$storyID]", $story->estimate, "class='form-control'"); ?></td>
          <td><?php echo html::select("category[$storyID]", $lang->story->categoryList, $story->category, 'class=form-control chosen');?></td>
          <td <?php echo zget($visibleFields, 'pri', "class='hidden'")?>><?php echo html::select("pris[$storyID]",     $priList, $story->pri, 'class=form-control');?></td>
          <td class='text-left<?php echo zget($visibleFields, 'assignedTo', ' hidden')?>'><?php echo html::select("assignedTo[$storyID]",     $users, $story->assignedTo, "class='form-control chosen'");?></td>
          <td <?php echo zget($visibleFields, 'source', "class='hidden'")?>><?php echo html::select("sources[$storyID]",  $sourceList, $story->source, "class='form-control chosen' id='source_$storyID'");?></td>
          <?php
          if($story->source == 'meeting' or $story->source == 'researchreport')
          {
              $objects = $story->source == 'meeting' ? $meetings : $researchReports;
              $html = html::select("sourceNote[$storyID]", $objects, $story->sourceNote, "class='form-control chosen' id='sourceNote_$storyID'");
          }
          else
          {
              $html = html::input("sourceNote[$storyID]", $story->sourceNote, "class='form-control' id='sourceNote_$storyID'");
          }
          ?>
          <td class='<?php echo zget($visibleFields, 'source', 'hidden')?>' data-id="<?php echo $story->sourceNote;?>"><?php echo $html;?></td>
          <td class='story-<?php echo $story->status;?>'><?php echo $this->processStatus('story', $story);?></td>
          <td <?php echo zget($visibleFields, 'stage', "class='hidden'")?>><?php echo html::select("stages[$storyID]", $stageList, $story->stage, 'class="form-control"' . ($story->status == 'draft' ? ' disabled="disabled"' : ''));?></td>
          <td class='text-left<?php echo zget($visibleFields, 'closedBy', ' hidden')?>'><?php echo html::select("closedBys[$storyID]",     $users, $story->closedBy, "class='form-control" . ($story->status == 'closed' ? " chosen'" : "' disabled='disabled'"));?></td>

          <?php if($story->status == 'closed'):?>
          <td <?php echo zget($visibleFields, 'closedReason', "class='hidden'")?>>
            <table class='w-p100'>
              <tr>
                <td class='pd-0'>
                  <?php echo html::select("closedReasons[$storyID]", $reasonList, $story->closedReason, "class=form-control onchange=setDuplicateAndChild(this.value,$storyID) style='min-width: 70px'");?>
                </td>
                <td class='pd-0' id='<?php echo 'duplicateStoryBox' . $storyID;?>' <?php if($story->closedReason != 'duplicate') echo "style='display: none'";?>>
                <?php echo html::input("duplicateStoryIDList[$storyID]", '', "class='form-control' placeholder='{$lang->idAB}'");?>
                </td>
                <td class='pd-0' id='<?php echo 'childStoryBox' . $storyID;?>' <?php if($story->closedReason != 'subdivided') echo "style='display: none'";?>>
                <?php echo html::input("childStoriesIDList[$storyID]", '', "class='form-control' placeholder='{$lang->idAB}'");?>
                </td>
              </tr>
            </table>
          </td>
          <?php else:?>
          <td <?php echo zget($visibleFields, 'closedReason', "class='hidden'")?>><?php echo html::select("closedReasons[$storyID]", $reasonList, $story->closedReason, 'class="form-control" disabled="disabled"');?></td>
          <?php endif;?>
          <td <?php echo zget($visibleFields, 'keywords', "class='hidden'")?>><?php echo html::input("keywords[$storyID]", $story->keywords, 'class="form-control"');?></td>
          <?php foreach($extendFields as $extendField) echo "<td" . (($extendField->control == 'select' or $extendField->control == 'multi-select') ? " style='overflow: visible'" : '') . ">" . $this->loadModel('flow')->getFieldControl($extendField, $story, $extendField->field . "[{$storyID}]") . "</td>";?>
        </tr>
        <?php endforeach;?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan='<?php echo count($visibleFields) + ($branchProduct ? 3 : 2);?>' class='text-center form-actions'>
            <?php echo html::submitButton();?>
            <?php echo html::backButton();?>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</form>
<?php endif;?>
<?php include '../../common/view/footer.html.php';?>
