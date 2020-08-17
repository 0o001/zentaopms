<?php
class programplanModel extends model
{
    public function setMenu($programID, $productID)
    {
        return true;
    }

    public function getByID($planID)
    {
        $plan = $this->dao->select('*')->from(TABLE_PROJECT)->where('id')->eq($planID)->fetch();

        return $this->processPlan($plan);
    }

    public function getProjectsByProduct($productID)
    {
        $projects = $this->dao->select('project')->from(TABLE_PROJECTPRODUCT)->where('product')->eq($productID)->fetchPairs();

        return $projects;
    }

    public function getList($programID = 0, $productID = 0, $planID = 0, $type = '', $orderBy = 'id_asc')
    {
        $projects = $this->getProjectsByProduct($productID);

        $plans = $this->dao->select('*')->from(TABLE_PROJECT)
            ->where('program')->eq($programID)
            ->andWhere('template')->eq('')
            ->beginIF($type != 'all')->andWhere('parent')->eq($planID)->fi()
            ->andWhere('deleted')->eq(0)
            ->beginIF($productID)->andWhere('id')->in($projects)->fi()
            ->orderBy($orderBy)
            ->fetchAll('id');

        return $this->processPlans($plans);
    }

    public function getByList($idList = array())
    {
        $plans = $this->dao->select('*')->from(TABLE_PROJECT)
            ->where('id')->in($idList)
            ->andWhere('type')->eq('project')
            ->fetchAll('id');

        return $this->processPlans($plans);
    }

    public function getPlanPairsForBudget($programID = 0)
    {
        $pairs   = array();
        $plans   = $this->getPlans($programID);
        $program = $this->getByID($programID);
        foreach($plans as $planID => $plan)
        {
            $name = $plan->name;
            if($program->category == 'multiple') $name = $plan->productName . '/' . $name;
            $pairs[$planID] = '/'.$name;

            foreach($plan->children as $childID => $child)
            {
                $childName = $name . '/' . $child->name;
                $pairs[$childID] = $childName;
            }
        }

        return $pairs;
    }

    public function getPlans($programID = 0, $productID = 0, $orderBy = 'id_asc')
    {
        $plans = $this->getList($programID, $productID, 0, 'all', $orderBy);

        $parents = array();
        foreach($plans as $planID => $plan)
        {
            $plan->parent == 0 ? $parents[$planID] = $plan : $children[$plan->parent][] = $plan;
        }

        foreach($parents as $planID => $plan) $parents[$planID]->children = isset($children[$planID]) ? $children[$planID] : array();

        return $parents;
    }

    public function getPairs($programID, $productID = 0, $type = 'all')
    {
        $plans = $this->getPlans($programID, $productID);

        $pairs = array(0 => '');
        foreach($plans as $plan)
        {
            $pairs[$plan->id] = $plan->name;
            if(!empty($plan->children))
            {
                foreach($plan->children as $child) $pairs[$child->id] = $plan->name . '/' . $child->name;
            }
        }

        return $pairs;
    }

    public function getDataForGantt($programID, $productID, $baselineID = 0)
    {
        $this->loadModel('stage');

        $plans = $this->getList($programID, $productID, 0, 'all');
        if($baselineID)
        {
            $baseline = $this->loadModel('cm')->getByID($baselineID);
            $oldData  = json_decode($baseline->data);
            $oldPlans = $oldData->stage;
            foreach($oldPlans as $id => $oldPlan)
            {
                if(!isset($plans[$id])) continue;
                $plans[$id]->version   = $oldPlan->version;
                $plans[$id]->name      = $oldPlan->name;
                $plans[$id]->milestone = $oldPlan->milestone;
                $plans[$id]->begin     = $oldPlan->begin;
                $plans[$id]->end       = $oldPlan->end;
            }
        }

        $datas       = array();
        $planIDList  = array();
        $isMilestone = "<icon class='icon icon-flag icon-sm red'></icon> ";
        $stageIndex  = array();
        foreach($plans as $plan)
        {
            $planIDList[$plan->id] = $plan->id;

            $start = $plan->begin == '0000-00-00' ? '' : date('d-m-Y', strtotime($plan->begin));
            $end   = $plan->end   == '0000-00-00' ? '' : $plan->end;

            $data = new stdclass();
            $data->id           = $plan->id;
            $data->text         = empty($plan->milestone) ? $plan->name : $isMilestone . $plan->name;
            $data->percent      = $plan->percent;
            $data->attribute    = zget($this->lang->stage->typeList, $plan->attribute);
            $data->milestone    = zget($this->lang->programplan->milestoneList, $plan->milestone);
            $data->start_date   = $start;
            $data->deadline     = $end;
            $data->realStarted  = $plan->realStarted == '0000-00-00' ? '' : $plan->realStarted;
            $data->realFinished = $plan->realFinished  == '0000-00-00' ? '' : $plan->realFinished;
            $data->duration     = helper::diffDate($plan->end, $plan->begin) + 1;; 
            $data->parent       = $plan->parent;
            $data->open         = true;

            if($data->start_date == '' or $data->deadline == '') $data->duration = 0;

            $datas['data'][] = $data;
            $stageIndex[]    = array('planID' => $plan->id, 'progress' => array('totalConsumed' => 0, 'totalReal' => 0));
        }

        $taskSign = "<span>[ T ] </span>";
        $taskPri  = "<span class='label-pri label-pri-%s' title='%s'>%s</span> ";

        /* Judge whether to display tasks under the stage. */
        $owner        = $this->app->user->account;
        $module       = 'programplan';
        $section      = 'browse';
        $object       = 'stageCustom';
        $setting      = $this->loadModel('setting');
        $selectCustom = $setting->getItem("owner={$owner}&module={$module}&section={$section}&key={$object}");

        $tasks = array();
        if(strpos($selectCustom, 'task') !== false)
        {
            $tasks = $this->dao->select('*')->from(TABLE_TASK)->where('deleted')->eq(0)->andWhere('project')->in($planIDList)->fetchAll('id');
        }

        if($baselineID)
        {
            $oldTasks = $oldData->task;
            foreach($oldTasks as $id => $oldTask)
            {
                if(!isset($tasks->$id)) continue;
                $tasks->$id->version    = $oldTask->version;
                $tasks->$id->name       = $oldTask->name;
                $tasks->$id->estStarted = $oldTask->estStarted;
                $tasks->$id->deadline   = $oldTask->deadline;
            }
        }

        foreach($tasks as $task)
        {
            $start = $task->estStarted == '0000-00-00' ? '' : date('d-m-Y', strtotime($task->estStarted));
            $end   = $task->deadline   == '0000-00-00' ? '' : $task->deadline;

            $realStarted  = $task->realStarted  == '0000-00-00' ? '' : $task->realStarted;
            $realFinished = $task->finishedDate == '0000-00-00 00:00:00' ? '' : substr($task->finishedDate, 5, 11);
            $priIcon      = sprintf($taskPri, $task->pri, $task->pri, $task->pri);

            $data = new stdclass();
            $data->id           = $task->project . '-' . $task->id;
            $data->text         = $taskSign . $priIcon . $task->name;
            $data->percent      = '';
            $data->attribute    = '';
            $data->milestone    = '';
            $data->start_date   = $start;
            $data->deadline     = $end;
            $data->realStarted  = $realStarted;
            $data->realFinished = $realFinished;
            $data->duration     = helper::diffDate($task->deadline, $task->estStarted) + 1;
            $data->parent       = $task->parent > 0 ? $task->project . '-' . $task->parent : $task->project;
            $data->open         = true;
            $progress           = $task->consumed ? round($task->consumed / ($task->left + $task->consumed), 3) * 100 : 0;
            $data->taskProgress = $progress . '%';

            if($data->start_date == '' or $data->deadline == '') $data->duration = 0;

            $datas['data'][] = $data;
            foreach($stageIndex as $index => $stage)
            {
                if($stage['planID'] == $task->project)
                {
                    $stageIndex[$index]['progress']['totalConsumed'] += $task->consumed;
                    $stageIndex[$index]['progress']['totalReal']     += ($task->left + $task->consumed);
                }
            }
        }

        /* Calculate the progress of the phase. */
        foreach($stageIndex as $index => $stage)
        {
            $progress  = empty($stage['progress']['totalConsumed']) ? 0 : round($stage['progress']['totalConsumed'] / $stage['progress']['totalReal'], 3) * 100;
            $progress .= '%';
            $datas['data'][$index]->taskProgress = $progress;
        }

        return json_encode($datas);
    }

    public function getTotalPercent($plan)
    {
        $plans = $this->getList($plan->program, $plan->product, $plan->parent);

        $totalPercent = 0;
        foreach($plans as $planID => $planObj)
        {
            if($planID == $plan->id) continue;
            $totalPercent += $planObj->percent;
        }

        return $totalPercent;
    }

    public function processPlans($plans)
    {
        foreach($plans as $planID => $plan)
        {
            $plans[$planID] = $this->processPlan($plan);
        }

        return $plans;
    }

    public function processPlan($plan)
    {
        $plan->setMilestone = true;

        if($plan->parent)
        {
            $attribute = $this->dao->select('attribute')->from(TABLE_PROJECT)->where('id')->eq($plan->parent)->fetch('attribute');
            $plan->attribute = $attribute == 'develop' ? $attribute : $plan->attribute;
        }
        else
        {
            $milestones = $this->dao->select('count(*) AS count')->from(TABLE_PROJECT)
                ->where('parent')->eq($plan->id)
                ->andWhere('milestone')->eq(1)
                ->andWhere('deleted')->eq(0)
                ->fetch('count');
            if($milestones > 0)
            {
                $plan->milestone    = 0;
                $plan->setMilestone = false;
            }
        }

        $plan->begin = $plan->begin == '0000-00-00' ? '' : $plan->begin;
        $plan->end  = $plan->end  == '0000-00-00' ? '' : $plan->end;
        $plan->realStarted = $plan->realStarted == '0000-00-00' ? '' : $plan->realStarted;
        $plan->realFinished  = $plan->realFinished  == '0000-00-00' ? '' : $plan->realFinished;

        $plan->product     = $this->loadModel('product')->getProductIDByProject($plan->id);
        $plan->productName = $this->dao->findByID($plan->product)->from(TABLE_PRODUCT)->fetch('name');

        return $plan;
    }
    
    public function getDuration($begin, $end)
    {
        $duration = $this->loadModel('holiday')->getActualWorkingDays($begin, $end);
        return count($duration);
    }

    public function create($programID = 0, $parentID = 0, $productID = 0)
    {
        $data = (array)fixer::input('post')->get();
        extract($data);

        if(!$this->isCreateTask($parentID)) return dao::$errors['message'][] = $this->lang->programplan->error->createdTask;
        $parentStage = '';
        if($parentID)
        {
            $parentData  = $this->getByID($parentID);
            $parentStage = $parentData->attribute;
        }

        $attributes = array_values($attributes);
        $milestone  = array_values($milestone);
        $datas = array();
        foreach($names as $key => $name)
        {
            if(empty($name)) continue;

            $plan = new stdclass();
            $plan->id           = isset($planIDs[$key]) ? $planIDs[$key] : '';
            $plan->type         = 'project';
            $plan->program      = (int)$programID;
            $plan->parent       = $parentID;
            $plan->name         = $names[$key];
            $plan->percent      = $percents[$key];
            $plan->attribute    = empty($parentID) ? $attributes[$key] : $parentStage;
            $plan->milestone    = $milestone[$key];
            $plan->begin        = empty($begin[$key]) ? '0000-00-00' : $begin[$key];
            $plan->end          = empty($end[$key]) ? '0000-00-00' : $end[$key];
            $plan->realStarted  = empty($realStarted[$key]) ? '0000-00-00' : $realStarted[$key];
            $plan->realFinished = empty($realFinished[$key]) ? '0000-00-00' : $realFinished[$key];
            $plan->output       = empty($output[$key]) ? '' : implode(',', $output[$key]);

            $datas[] = $plan;
        }

        $totalPercent = 0;
        $devCounts    = 0;
        $milestone    = 0;
        foreach($datas as $plan)
        {
            if($plan->percent and !preg_match("/^[0-9]+(.[0-9]{1,3})?$/", $plan->percent))
            {
                dao::$errors['message'][] = $this->lang->programplan->error->percentNumber;
                return false;
            }
            if($plan->end != '0000-00-00' and $plan->end < $plan->begin)
            {
                dao::$errors['message'][] = $this->lang->programplan->error->planFinishSmall;
                return false;
            }

            /* Check dev stage counts which should not be over one for parent plan. */
            if($parentID == 0)
            {
                if($plan->attribute == 'develop') $devCounts += 1;
                if($devCounts > 1)
                {
                    dao::$errors['message'][] = $this->lang->programplan->error->onlyOneDev;
                    return false;
                }
            }

            if($plan->begin == '0000-00-00') $plan->begin = '';
            if($plan->end  == '0000-00-00') $plan->end  = '';
            foreach(explode(',', $this->config->programplan->create->requiredFields) as $field)
            {
                $field = trim($field);
                if($field and empty($plan->$field))
                {
                    dao::$errors['message'][] = sprintf($this->lang->error->notempty, $this->lang->programplan->$field);
                    return false;
                }
            }

            if($plan->percent)
            {
                $plan->percent = (float)$plan->percent;
                $totalPercent += $plan->percent;
            }

            if($plan->milestone) $milestone = 1;
        }

        if($totalPercent > 100)
        {
            dao::$errors['message'][] = $this->lang->programplan->error->percentOver;
            return false;
        }

        $this->post->set('products', array(0 => $productID));//目前计划阶段用的就是迭代，迭代和产品会有个绑定关系，调用迭代（项目）模块的updateProducts方法来实现这个绑定关系，需要往post里塞入产品数据。

        $account = $this->app->user->account;
        $now     = helper::now();
        foreach($datas as $data)
        {
            /* Set planDuration and realDuration. */
            $data->planDuration = $this->getDuration($data->begin, $data->end);
            $data->realDuration = $this->getDuration($data->realStarted, $data->realFinished);

            $projectChanged = false;
            $data->days     = helper::diffDate($data->end, $data->begin) + 1;
            if($data->id)
            {
                $planID = $data->id;
                unset($data->id);

                $oldPlan     = $this->getByID($planID);
                $planChanged = ($oldPlan->name != $data->name || $oldPlan->milestone != $data->milestone || $oldPlan->begin != $data->begin || $oldPlan->end != $data->end);

                if($planChanged) $data->version = $oldPlan->version + 1;
                $this->dao->update(TABLE_PROJECT)->data($data)
                    ->autoCheck()
                    ->batchCheck($this->config->programplan->edit->requiredFields, 'notempty')
                    ->checkIF($plan->percent != '', 'percent', 'float')
                    ->where('id')->eq($planID)
                    ->exec();

                if($planChanged)
                {
                    $spec = new stdclass();
                    $spec->project   = $planID;
                    $spec->version   = $data->version;
                    $spec->name      = $data->name;
                    $spec->milestone = $data->milestone;
                    $spec->begin     = $data->begin;
                    $spec->end       = $data->end;

                    $this->dao->insert(TABLE_PROJECTSPEC)->data($spec)->exec();
                }
            }
            else
            {
                unset($data->id);
                $data->status        = 'wait';
                $data->acl           = 'open';
                $data->version       = 1;
                $data->parentVersion = $data->parent == 0 ? 0 : $this->dao->findByID($data->parent)->from(TABLE_PROJECT)->fetch('version');
                $data->team          = substr($data->name,0, 30);
                $data->openedBy      = $account;
                $data->openedDate    = $now;
                $data->openedVersion = $this->config->version;
                $this->dao->insert(TABLE_PROJECT)->data($data)
                    ->autoCheck()
                    ->batchCheck($this->config->programplan->create->requiredFields, 'notempty')
                    ->checkIF($plan->percent != '', 'percent', 'float')
                    ->exec();

                if(!dao::isError())
                {
                    $planID = $this->dao->lastInsertID();
                    $this->loadModel('project')->updateProducts($planID);

                    $spec = new stdclass();
                    $spec->project   = $planID;
                    $spec->version   = $data->version;
                    $spec->name      = $data->name;
                    $spec->milestone = $data->milestone;
                    $spec->begin     = $data->begin;
                    $spec->end       = $data->end;

                    $this->dao->insert(TABLE_PROJECTSPEC)->data($spec)->exec();
                }
            }

            /* If child plans has milestone, update parent plan set milestone eq 0 . */
            if($parentID and $milestone) $this->dao->update(TABLE_PROJECT)->set('milestone')->eq(0)->where('id')->eq($parentID)->exec();

            if(dao::isError()) die(js::error(dao::getError()));
        }
    }

    public function update($planID = 0)
    {
        $oldPlan = $this->getByID($planID);
        $plan    = fixer::input('post')
            ->remove('uid')
            ->setDefault('begin', '0000-00-00')
            ->setDefault('end', '0000-00-00')
            ->setDefault('realStarted', '0000-00-00')
            ->setDefault('realFinished', '0000-00-00')
            ->join('output', ',')
            ->get();

        if($plan->begin == '0000-00-00') dao::$errors['begin'][] = sprintf($this->lang->error->notempty, $this->lang->programplan->begin);
        if($plan->end  == '0000-00-00') dao::$errors['end'][]  = sprintf($this->lang->error->notempty, $this->lang->programplan->end);

        $planChanged = ($oldPlan->name != $plan->name || $oldPlan->milestone != $plan->milestone || $oldPlan->begin != $plan->begin || $oldPlan->end != $plan->end);

        /* Judge whether the workload ratio exceeds 100%. */
        $oldPlan->parent = $plan->parent;
        $totalPercent  = $this->getTotalPercent($oldPlan);
        $totalPercent += (float)$plan->percent;
        if($totalPercent > 100) return dao::$errors['percent'][] = $this->lang->programplan->error->percentOver;

        if($plan->parent == 0)
        {
            $projects  = $this->getProjectsByProduct($oldPlan->product);
            $devCounts = $this->dao->select('count(*) AS count')->from(TABLE_PROJECT)
                ->where('program')->eq($oldPlan->program)
                ->andWhere('deleted')->eq(0)
                ->andWhere('parent')->eq(0)
                ->andWhere('attribute')->eq('dev')
                ->andWhere('id')->ne($oldPlan->id)
                ->andWhere('id')->in($projects)
                ->fetch('count');

            if(isset($plan->attribute) && $plan->attribute == 'dev') $devCounts += 1;
            if($devCounts > 1) dao::$errors['attribute'][] = $this->lang->programplan->error->onlyOneDev;
        }

        if($plan->parent > 0)
        {
            /* If child plans has milestone, update parent plan set milestone eq 0 . */
            $parentPlan = $this->getByID($plan->parent);
            if($plan->milestone and $parentPlan->milestone) $this->dao->update(TABLE_PROJECT)->set('milestone')->eq(0)->where('id')->eq($oldPlan->parent)->exec();
        }

        if(dao::isError()) return false;

        /* Set planDuration and realDuration. */
        $plan->planDuration = $this->getDuration($plan->begin, $plan->end);
        $plan->realDuration = $this->getDuration($plan->realStarted, $plan->realFinished);

        if($plan->parent) $plan->attribute = $parentPlan->attribute;

        if($planChanged) $plan->version = $oldPlan->version + 1;
        $this->dao->update(TABLE_PROJECT)->data($plan)
            ->autoCheck()
            ->batchCheck($this->config->programplan->edit->requiredFields, 'notempty')
            ->checkIF($plan->end != '0000-00-00', 'end', 'ge', $plan->begin)
            ->checkIF($plan->percent != false, 'percent', 'float')
            ->where('id')->eq($planID)
            ->exec();

        if(dao::isError()) return false;

        if($planChanged)
        {
            $spec = new stdclass();
            $spec->project   = $planID;
            $spec->version   = $plan->version;
            $spec->name      = $plan->name;
            $spec->milestone = $plan->milestone;
            $spec->begin     = $plan->begin;
            $spec->end       = $plan->end;

            $this->dao->insert(TABLE_PROJECTSPEC)->data($spec)->exec();
        }

        return common::createChanges($oldPlan, $plan);
    }

    public function printCell($col, $plan, $users)
    {
        $id = $col->id;
        if($col->show)
        {
            $class  = 'c-' . $id;
            $title  = '';
            $idList = array('id','name','output','percent','attribute','milestone','version','openedBy','openedDate','begin','end','realStarted','realFinished');
            if(in_array($id,$idList))
            {
                $class .= ' text-left';
                $title  = "title='{$plan->$id}'";
                if($id == 'output') $class .= ' text-ellipsis';
                if(!empty($plan->children)) $class .= ' has-child';
            }
            else
            {
                $class .= ' text-center';
            }
            if($id == 'actions') $class .= ' c-actions';

            echo "<td class='{$class}' {$title}>";
            if(isset($this->config->bizVersion)) $this->loadModel('flow')->printFlowCell('programplan', $plan, $id);
            switch($id)
            {
            case 'id':
                echo sprintf('%03d', $plan->id);
                break;
            case 'name':
                if($plan->parent > 0) echo '<span class="label label-badge label-light" title="' . $this->lang->programplan->children . '">' . $this->lang->programplan->childrenAB . '</span> ';
                echo $plan->name;
                if(!empty($plan->children)) echo '<a class="plan-toggle" data-id="' . $plan->id . '"><i class="icon icon-angle-double-right"></i></a>';
                break;
            case 'percent':
                echo $plan->percent . '%';
                break;
            case 'attribute':
                echo zget($this->lang->stage->typeList, $plan->attribute, '');
                break;
            case 'milestone':
                echo zget($this->lang->programplan->milestoneList, $plan->milestone, 0);
                break;
            case 'begin':
                echo $plan->begin;
                break;
            case 'end':
                echo $plan->end;
                break;
            case 'realStarted':
                echo $plan->realStarted;
                break;
            case 'realFinished':
                echo $plan->realFinished;
                break;
            case 'output':
                echo $plan->output;
                break;
            case 'version':
                echo $plan->version;
                break;
            case 'openedBy':
                echo zget($users, $plan->openedBy);
                break;
            case 'openedDate':
                echo substr($plan->openedDate, 5, 11);
                break;
            case 'editedBy':
                echo zget($users, $plan->editedBy);
                break;
            case 'editedDate':
                echo substr($plan->editedDate, 5, 11);
                break;
            case 'actions':
                common::printIcon('project', 'start', "projectID={$plan->id}", $plan, 'list', '', '', 'iframe', true);
                common::printIcon('task', 'create', "projectID={$plan->id}", $plan, 'list');
                if($this->isCreateTask($plan->id))
                {
                    common::printIcon('programplan', 'create', "program={$plan->program}&productID=$plan->product&planID=$plan->id", $plan, 'list', 'treemap', '', '', '', '', $this->lang->programplan->createSubPlan);
                }
                else
                {
                    $disabled = ($plan->parent == 0) ? ' disabled' : '';
                    echo html::a('javascript:alert("' . $this->lang->programplan->error->createdTask . '");', '<i class="icon-programplan-create icon-treemap"></i>', '', 'class="btn ' . $disabled . '"');
                }

                common::printIcon('programplan', 'edit', "planID=$plan->id", $plan, 'list', '', '', 'iframe', true);
                $disabled = !empty($plan->children) ? ' disabled' : '';
                if(common::hasPriv('programplan', 'delete', $plan))
                {
                    $deleteURL = helper::createLink('programplan', 'delete', "planID=$plan->id&confirm=yes");
                    echo html::a("javascript:ajaxDelete(\"$deleteURL\", \"programplanForm\", confirmDelete)", '<i class="icon icon-close"></i>', '', "title='{$this->lang->programplan->delete}' class='btn $disabled'");
                }
                break;
            }
            echo '</td>';
        }
    }

    public function isCreateTask($planID)
    {
        $task = $this->dao->select('*')->from(TABLE_TASK)->where('project')->eq($planID)->limit(1)->fetch();
        return empty($task) ? true : false;
    }

    public static function isClickable($plan, $action)
    {
        $action = strtolower($action);

        if($action == 'create' and $plan->parent) return false;

        return true;
    }

    public function getMilestones($programID = 0)
    {
        return $this->dao->select('id, name')->from(TABLE_PROJECT)
            ->where('program')->eq($programID)
            ->andWhere('type')->eq('project')
            ->andWhere('milestone')->eq(1)
            ->andWhere('deleted')->eq(0)
            ->orderBy('begin asc')
            ->fetchPairs();
    }

    public function getMilestoneByProduct($productID)
    {
        return $this->dao->select('t1.id, t1.name')->from(TABLE_PROJECT)->alias('t1')
            ->leftJoin(TABLE_PROJECTPRODUCT)->alias('t2')->on('t1.id=t2.project')
            ->where('t2.product')->eq($productID)
            ->andWhere('t1.type')->eq('project')
            ->andWhere('t1.milestone')->eq(1)
            ->andWhere('t1.deleted')->eq(0)
            ->orderBy('t1.begin asc')
            ->fetchPairs();
    }

    public function isParent($planID)
    {
        $children = $this->dao->select('id')->from(TABLE_PROJECT)->where('parent')->eq($planID)->andWhere('deleted')->eq('0')->fetch();
        return empty($children) ? false : true;
    }

    public function getParentStageList($planID, $productID)
    {
        $projects = $this->getProjectsByProduct($productID);
        unset($projects[$planID]);

        $parentStage = $this->dao->select('id,name')->from(TABLE_PROJECT)
            ->where('id')->in($projects)
            ->andWhere('type')->eq('project')
            ->andWhere('program')->eq($this->session->program)
            ->andWhere('parent')->eq(0)
            ->andWhere('deleted')->eq('0')
            ->fetchPairs('id');

        foreach($parentStage as $key => $stage)
        {
            $isCreate = $this->isCreateTask($key);
            if($isCreate === false) unset($parentStage[$key]);
        }
        $parentStage[0] = $this->lang->programplan->emptyParent;
        ksort($parentStage);

        return $parentStage;
    }
}
