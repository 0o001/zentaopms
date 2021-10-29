<?php
/**
 * The model file of kanban module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2021 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Shujie Tian <tianshujie@easycorp.ltd>
 * @package     kanban
 * @version     $Id: model.php 5118 2021-10-22 10:18:41Z $
 * @link        https://www.zentao.net
 */
?>
<?php
class kanbanModel extends model
{
    /**
     * Get Kanban by execution id.
     *
     * @param  int    $executionID
     * @param  string $objectType all|story|bug|task
     * @param  string $groupBy
     * @access public
     * @return array
     */
    public function getExecutionKanban($executionID, $browseType = 'all', $groupBy = 'default')
    {
        $lanes = $this->dao->select('*')->from(TABLE_KANBANLANE)
            ->where('execution')->eq($executionID)
            ->andWhere('deleted')->eq(0)
            ->beginIF($browseType != 'all')->andWhere('type')->eq($browseType)
            ->beginIF($groupBy != 'default')->andWhere('extra')->eq($groupBy)
            ->fetchAll('id');

        if(empty($lanes)) return array();

        foreach($lanes as $lane) $this->updateCards($lane);

        $columns = $this->dao->select('*')->from(TABLE_KANBANCOLUMN)
            ->where('deleted')->eq(0)
            ->andWhere('lane')->in(array_keys($lanes))
            ->fetchGroup('lane', 'id');

        /* Get parent column type pairs. */
        $parentTypes = $this->dao->select('id, type')->from(TABLE_KANBANCOLUMN)
            ->where('deleted')->eq(0)
            ->andWhere('lane')->in(array_keys($lanes))
            ->andWhere('parent')->eq(-1)
            ->fetchPairs('id', 'type');

        /* Get group objects. */
        if($browseType == 'all' or $browseType == 'story') $objectGroup['story'] = $this->loadModel('story')->getExecutionStories($executionID);
        if($browseType == 'all' or $browseType == 'bug')   $objectGroup['bug']   = $this->loadModel('bug')->getExecutionBugs($executionID);
        if($browseType == 'all' or $browseType == 'task')  $objectGroup['task']  = $this->loadModel('execution')->getKanbanTasks($executionID, "id");

        /* Build kanban group data. */
        $kanbanGroup = array();
        foreach($lanes as $laneID => $lane)
        {
            $laneData   = array();
            $columnData = array();
            $laneType   = $lane->type;

            $laneData['id']              = $laneType;
            $laneData['name']            = $lane->name;
            $laneData['color']           = $lane->color;
            $laneData['order']           = $lane->order;
            $laneData['defaultCardType'] = $laneType;

            foreach($columns[$laneID] as $columnID => $column)
            {
                $columnData[$column->id]['id']         = $laneType . '-' . $column->type;
                $columnData[$column->id]['columnID']   = $columnID;
                $columnData[$column->id]['type']       = $column->type;
                $columnData[$column->id]['name']       = $column->name;
                $columnData[$column->id]['color']      = $column->color;
                $columnData[$column->id]['limit']      = $column->limit;
                $columnData[$column->id]['laneType']   = $laneType;
                $columnData[$column->id]['asParent']   = $column->parent == -1 ? true : false;

                if($column->parent > 0)
                {
                    $columnData[$column->id]['parentType'] = zget($parentTypes, $column->parent, '');
                }

                $cardOrder  = 1;
                $cardIdList = array_filter(explode(',', $column->cards));
                foreach($cardIdList as $cardID)
                {
                    $cardData = array();
                    $objects  = zget($objectGroup, $laneType, array());
                    $object   = zget($objects, $cardID, array());

                    $cardData['id']         = $object->id;
                    $cardData['order']      = $cardOrder;
                    $cardData['pri']        = $object->pri ? $object->pri : '';
                    $cardData['estimate']   = $laneType == 'bug' ? '' : $object->estimate;
                    $cardData['assignedTo'] = $object->assignedTo;
                    $cardData['deadline']   = $laneType == 'task' ? $object->deadline : '';
                    $cardData['severity']   = $laneType == 'bug' ? $object->severity : '';

                    if($laneType == 'task')
                    {
                        $cardData['name'] = $object->name;
                    }
                    else
                    {
                        $cardData['title'] = $object->title;
                    }

                    $laneData['cards'][$column->type][] = $cardData;
                    $cardOrder ++;
                }
                if(!isset($laneData['cards'][$column->type])) $laneData['cards'][$column->type] = array();
            }

            $kanbanGroup[$laneType]['id']              = $laneType;
            $kanbanGroup[$laneType]['columns']         = array_values($columnData);
            $kanbanGroup[$laneType]['lanes'][]         = $laneData;
            $kanbanGroup[$laneType]['defaultCardType'] = $laneType;
        }

        return $kanbanGroup;
    }

    /**
     * Add execution Kanban lanes and columns.
     *
     * @param  int    $executionID
     * @param  string $type all|story|bug|task
     * @param  string $groupBy default
     * @access public
     * @return void
     */
    public function createLanes($executionID, $type = 'all', $groupBy = 'default')
    {
        if($groupBy == 'default')
        {
            foreach($this->config->kanban->default as $type => $lane)
            {
                $lane->type      = $type;
                $lane->execution = $executionID;
                $this->dao->insert(TABLE_KANBANLANE)->data($lane)->exec();

                $laneID = $this->dao->lastInsertId();
                $this->createColumns($laneID, $type, $executionID);
            }
        }
        else
        {
            $this->loadModel($type);

            $groupList = array();
            $table     = zget($this->config->objectTables, $type);

            if($groupBy == 'story' or $type == 'story')
            {
                $selectField = $groupBy == 'story' ? "t1.$groupBy" : "t2.$groupBy";
                $groupList = $this->dao->select($selectField)->from(TABLE_PROJECTSTORY)->alias('t1')
                    ->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story=t2.id')
                    ->where('t1.project')->eq($executionID)
                    ->andWhere('t2.deleted')->eq(0)
                    ->orderBy($groupBy . '_desc')
                    ->fetchPairs();
            }
            else
            {
                $groupList = $this->dao->select($groupBy)->from($table)
                    ->where('execution')->eq($executionID)
                    ->beginIF($type == 'task')->andWhere('parent')->ge(0)->fi()
                    ->andWhere('deleted')->eq(0)
                    ->orderBy($groupBy . '_desc')
                    ->fetchPairs();
            }

            $objectPairs = array();
            if($groupBy == 'module') $objectPairs = $this->dao->select('id,name')->from(TABLE_MODULE)->where('type')->eq($type)->andWhere('deleted')->eq('0')->fetchPairs();
            if($groupBy == 'story')  $objectPairs = $this->dao->select('id,title')->from(TABLE_STORY)->where('deleted')->eq(0)->fetchPairs();
            if($groupBy == 'bug')    $objectPairs = $this->loadModel('user')->getPairs('noletter');

            $laneName  = '';
            $laneOrder = 5;
            foreach($groupList as $groupKey)
            {
                if($groupKey)
                {
                    if(strpos('module,story,assignedTo', $groupBy) !== false)
                    {
                        $laneName = zget($objectPairs, $groupKey);
                    }
                    else
                    {
                        $laneName = zget($this->lang->$type->{$groupBy . 'List'}, $groupKey);
                    }
                }
                else
                {
                    $laneName = $this->lang->kanban->noGroup;
                }

                $lane = new stdClass();
                $lane->execution = $executionID;
                $lane->type      = $type;
                $lane->extra     = $groupBy;
                $lane->name      = $laneName;
                $lane->color     = '#7ec5ff';
                $lane->order     = $laneOrder;

                $laneOrder += 5;
                $this->dao->insert(TABLE_KANBANLANE)->data($lane)->exec();

                $laneID = $this->dao->lastInsertId();
                $this->createColumns($laneID, $type, $executionID, $groupBy, $groupKey);
            }
        }
    }

    /**
     * createColumn
     *
     * @param  int    $laneID
     * @param  string $type story|bug|task
     * @param  int    $executionID
     * @param  string $groupBy
     * @param  string $groupValue
     * @access public
     * @return void
     */
    public function createColumns($laneID, $type, $executionID, $groupBy = '', $groupValue = '')
    {
        $objects = array();

        if($type == 'story') $objects = $this->loadModel('story')->getExecutionStories($executionID, 0, 0, 't2.id_desc');
        if($type == 'bug')   $objects = $this->loadModel('bug')->getExecutionBugs($executionID);
        if($type == 'task')  $objects = $this->loadModel('execution')->getKanbanTasks($executionID);

        if(!empty($groupBy))
        {
            foreach($objects as $objectID => $object)
            {
                if($object->$groupBy != $groupValue) unset($objects[$objectID]);
            }
        }

        $devColumnID = $testColumnID = $resolvingColumnID = 0;
        if($type == 'story')
        {
            foreach($this->lang->kanban->storyColumn as $colType => $name)
            {
                $data = new stdClass();
                $data->lane  = $laneID;
                $data->name  = $name;
                $data->color = '#333';
                $data->type  = $colType;
                $data->cards = '';

                if(strpos(',developing,developed,', $colType) !== false) $data->parent = $devColumnID;
                if(strpos(',testing,tested,', $colType) !== false) $data->parent = $testColumnID;
                if(strpos(',develop,test,', $colType) !== false) $data->parent = -1;
                if(strpos(',ready,develop,test,', $colType) === false)
                {
                    $storyStatus = $this->config->kanban->storyColumnStatusList[$colType];
                    $storyStage  = $this->config->kanban->storyColumnStageList[$colType];
                    foreach($objects as $storyID => $story)
                    {
                        if($story->status == $storyStatus and $story->stage == $storyStage) $data->cards .= $storyID . ',';
                    }
                    if(!empty($data->cards)) $data->cards = ',' . $data->cards;
                }

                $this->dao->insert(TABLE_KANBANCOLUMN)->data($data)->exec();
                if($colType == 'develop') $devColumnID  = $this->dao->lastInsertId();
                if($colType == 'test')    $testColumnID = $this->dao->lastInsertId();
            }
        }
        elseif($type == 'bug')
        {
            foreach($this->lang->kanban->bugColumn as $colType => $name)
            {
                $data = new stdClass();
                $data->lane  = $laneID;
                $data->name  = $name;
                $data->color = '#333';
                $data->type  = $colType;
                $data->cards = '';
                if(strpos(',fixing,fixed,', $colType) !== false) $data->parent = $resolvingColumnID;
                if(strpos(',testing,tested,', $colType) !== false) $data->parent = $testColumnID;
                if(strpos(',resolving,test,', $colType) !== false) $data->parent = -1;
                if(strpos(',resolving,fixing,test,testing,tested,', $colType) === false)
                {
                    $bugStatus = $this->config->kanban->bugColumnStatusList[$colType];
                    foreach($objects as $bugID => $bug)
                    {
                        if($colType == 'unconfirmed' and $bug->status == $bugStatus and $bug->confirmed == 0)
                        {
                            $data->cards .= $bugID . ',';
                        }
                        elseif($colType == 'confirmed' and $bug->status == $bugStatus and $bug->confirmed == 1)
                        {
                            $data->cards .= $bugID . ',';
                        }
                        elseif($bug->status == $bugStatus)
                        {
                            $data->cards .= $bugID . ',';
                        }
                    }
                    if(!empty($data->cards)) $data->cards = ',' . $data->cards;
                }
                $this->dao->insert(TABLE_KANBANCOLUMN)->data($data)->exec();
                if($colType == 'resolving') $resolvingColumnID = $this->dao->lastInsertId();
                if($colType == 'test')      $testColumnID      = $this->dao->lastInsertId();
            }
        }
        elseif($type == 'task')
        {
            foreach($this->lang->kanban->taskColumn as $colType => $name)
            {
                $data = new stdClass();
                $data->lane  = $laneID;
                $data->name  = $name;
                $data->color = '#333';
                $data->type  = $colType;
                $data->cards = '';
                if(strpos(',developing,developed,', $colType) !== false) $data->parent = $devColumnID;
                if($colType == 'develop') $data->parent = -1;
                if(strpos(',develop,', $colType) === false)
                {
                    $taskStatus = $this->config->kanban->taskColumnStatusList[$colType];
                    foreach($objects as $taskID => $task)
                    {
                        if($task->status == $taskStatus) $data->cards .= $taskID . ',';

                    }
                    if(!empty($data->cards)) $data->cards = ',' . $data->cards;
                }
                $this->dao->insert(TABLE_KANBANCOLUMN)->data($data)->exec();
                if($colType == 'develop') $devColumnID = $this->dao->lastInsertId();
            }
        }
    }

    /**
     * Update column cards.
     *
     * @param  object $lane
     * @access public
     * @return void
     */
    public function updateCards($lane)
    {
        $laneType    = $lane->type;
        $executionID = $lane->execution;
        $cardPairs = $this->dao->select('*')->from(TABLE_KANBANCOLUMN)
            ->where('deleted')->eq(0)
            ->andWhere('lane')->eq($lane->id)
            ->fetchPairs('type' ,'cards');

        if($laneType == 'story')
        {
            $stories = $this->loadModel('story')->getExecutionStories($executionID);
            foreach($stories as $storyID => $story)
            {
                foreach($this->config->kanban->storyColumnStageList as $colType => $stage)
                {
                    if(strpos(',ready,develop,test,', $colType) !== false) continue;
                    if($colType == 'backlog' and $story->stage == $stage and strpos($cardPairs['ready'], ",$storyID,") === false and strpos($cardPairs['backlog'], ",$storyID,") === false)
                    {
                        $cardPairs['backlog'] .= empty($cardPairs['backlog']) ? ',' . $storyID . ',' : $storyID . ',';
                    }
                    elseif($story->stage == $stage and strpos($cardPairs[$colType], ",$storyID,") === false)
                    {
                        $cardPairs[$colType] .= empty($cardPairs[$colType]) ? ',' . $storyID . ',' : $storyID . ',';
                    }
                    elseif($story->stage != $stage and strpos($cardPairs[$colType], ",$storyID,") !== false)
                    {
                        $cardPairs[$colType] = str_replace(",$storyID,", ',', $cardPairs[$colType]);
                    }
                }
            }
        }
        elseif($laneType == 'bug')
        {
            $bugs = $this->loadModel('bug')->getExecutionBugs($executionID);
            foreach($bugs as $bugID => $bug)
            {
                foreach($this->config->kanban->bugColumnStatusList as $colType => $status)
                {
                    if(strpos(',resolving,fixing,test,testing,tested,', $colType) !== false) continue;
                    if($colType == 'unconfirmed' and $bug->status == $status and $bug->confirmed == 0 and strpos($cardPairs['unconfirmed'], ",$bugID,") === false and strpos($cardPairs['fixing'], ",$bugID,") === false)
                    {
                        $cardPairs['unconfirmed'] .= empty($cardPairs['unconfirmed']) ? ',' . $bugID . ',' : $bugID . ',';
                    }
                    elseif($colType == 'confirmed' and $bug->status == $status and $bug->confirmed == 1 and strpos($cardPairs['confirmed'], ",$bugID,") === false and strpos($cardPairs['fixing'], ",$bugID,") === false)
                    {
                        $cardPairs['confirmed'] .= empty($cardPairs['confirmed']) ? ',' . $bugID . ',' : $bugID . ',';
                    }
                    elseif($colType == 'fixed' and $bug->status == $status and strpos($cardPairs['fixed'], ",$bugID,") === false and strpos($cardPairs['testing'], ",$bugID,") === false and strpos($cardPairs['tested'], ",$bugID,") === false)
                    {
                        $cardPairs['confirmed'] .= empty($cardPairs['confirmed']) ? ',' . $bugID . ',' : $bugID . ',';
                    }
                    elseif($bug->status == $status and strpos($cardPairs[$colType], ",$bugID,") === false)
                    {
                        $cardPairs[$colType] .= empty($cardPairs[$colType]) ? ',' . $bugID . ',' : $bugID . ',';
                    }
                    elseif($bug->status != $status and strpos($cardPairs[$colType], ",$bugID,") !== false)
                    {
                        $cardPairs[$colType] = str_replace(",$bugID,", ',', $cardPairs[$colType]);
                    }
                }
            }
        }
        elseif($laneType == 'task')
        {
            $tasks = $this->loadModel('execution')->getKanbanTasks($executionID);
            foreach($tasks as $taskID => $task)
            {
                foreach($this->config->kanban->taskColumnStatusList as $colType => $status)
                {
                    if($colType == 'develop') continue;
                    if($task->status == $status and strpos($cardPairs[$colType], ",$taskID,") === false)
                    {
                        $cardPairs[$colType] .= empty($cardPairs[$colType]) ? ',' . $taskID . ',' : $taskID . ',';
                    }
                    elseif($task->status != $status and strpos($cardPairs[$colType], ",$taskID,") !== false)
                    {
                        $cardPairs[$colType] = str_replace(",$taskID,", ',', $cardPairs[$colType]);
                    }
                }
            }
        }

        foreach($cardPairs as $colType => $cards)
        {
            $this->dao->update(TABLE_KANBANCOLUMN)->set('cards')->eq($cards)->where('lane')->eq($lane->id)->andWhere('type')->eq($colType)->exec();
        }
    }

    /**
     * Get column by id.
     *
     * @param  int    $columnID
     * @access public
     * @return object
     */
    public function getColumnById($columnID)
    {
        $column = $this->dao->select('t1.*, t2.type as laneType')->from(TABLE_KANBANCOLUMN)->alias('t1')
            ->leftjoin(TABLE_KANBANLANE)->alias('t2')->on('t1.lane=t2.id')
            ->where('t1.id')->eq($columnID)
            ->andWhere('t1.deleted')->eq(0)
            ->fetch();

        if(!empty($column->parent)) $column->parentName = $this->dao->findById($column->parent)->from(TABLE_KANBANCOLUMN)->fetch('name');

        return $column;
    }

    /**
     * Get Column by column name.
     *
     * @param  string $name
     * @param  int    $laneID
     * @access public
     * @return object
     */
    public function getColumnByName($name, $laneID)
    {
        return $this->dao->select('*')
            ->from(TABLE_KANBANCOLUMN)
            ->where('name')->eq($name)
            ->andWhere('lane')->eq($laneID)
            ->fetch();
    }


    /**
     * Get lane by id.
     *
     * @param  int    $laneID
     * @access public
     * @return object
     */
    public function getLaneById($laneID)
    {
        return $this->dao->findById($laneID)->from(TABLE_KANBANLANE)->fetch();
    }

    /**
     * Set WIP limit.
     *
     * @param  int    $columnID
     * @access public
     * @return bool
     */
    public function setWIP($columnID)
    {
        $oldColumn = $this->getColumnById($columnID);
        $column    = fixer::input('post')
            ->cleanInt('limit')
            ->remove('WIPCount,noLimit')
            ->get();

        /* Check column limit. */
        $sumChildLimit = 0;
        if($oldColumn->parent == -1 and $column->limit != -1)
        {
            $childColumns = $this->dao->select('id,`limit`')->from(TABLE_KANBANCOLUMN)->where('parent')->eq($columnID)->fetchAll();
            foreach($childColumns as $childColumn)
            {
                if($childColumn->limit == -1)
                {
                    dao::$errors['limit'] = $this->lang->kanban->error->parentLimitNote;
                    return false;
                }

                $sumChildLimit += $childColumn->limit;
            }

            if($sumChildLimit > $column->limit)
            {
                dao::$errors['limit'] = $this->lang->kanban->error->parentLimitNote;
                return false;
            }
        }
        elseif($oldColumn->parent > 0)
        {
            $parentColumn = $this->getColumnByID($oldColumn->parent);
            if($parentColumn->limit != -1)
            {
                $siblingLimit = $this->dao->select('`limit`')->from(TABLE_KANBANCOLUMN)
                    ->where('`parent`')->eq($oldColumn->parent)
                    ->andWhere('id')->ne($columnID)
                    ->fetch('limit');

                $sumChildLimit = $siblingLimit + $column->limit;

                if($column->limit == -1 or $siblingLimit == -1 or $sumChildLimit > $parentColumn->limit)
                {
                    dao::$errors['limit'] = $this->lang->kanban->error->childLimitNote;
                    return false;
                }
            }
        }

        $this->dao->update(TABLE_KANBANCOLUMN)->data($column)
            ->autoCheck()
            ->checkIF($column->limit != -1, 'limit', 'gt', 0)
            ->batchcheck($this->config->kanban->setwip->requiredFields, 'notempty')
            ->where('id')->eq($columnID)
            ->exec();

        return dao::isError();
    }

    /**
     * Set lane info.
     *
     * @param  int    $laneID
     * @access public
     * @return bool
     */
    public function setLane($laneID)
    {
        $lane = fixer::input('post')->get();

        $this->dao->update(TABLE_KANBANLANE)->data($lane)
            ->autoCheck()
            ->batchcheck($this->config->kanban->setlane->requiredFields, 'notempty')
            ->where('id')->eq($laneID)
            ->exec();

        return dao::isError();
    }

    /**
     * Update lane column.
     *
     * @param  int    $columnID
     * @param  object $column
     * @access public
     * @return array
     */
    public function updateLaneColumn($columnID, $column)
    {
        $data = fixer::input('post')->get();

        $this->dao->update(TABLE_KANBANCOLUMN)->data($data)
            ->autoCheck()
            ->batchcheck($this->config->kanban->setlaneColumn->requiredFields, 'notempty')
            ->where('id')->eq($columnID)
            ->exec();

        if(dao::isError()) return;

        $changes = common::createChanges($column, $data);
        return $changes;
    }
}
