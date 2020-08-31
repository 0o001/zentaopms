<?php
/**
 * The model file of issue module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Yong Lei <leiyong@easycorp.ltd>
 * @package     issue
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php
class issueModel extends model
{
    /**
     * Get stakeholder issue list data.
     * @param  string $owner
     * @param  string $activityID
     * @param  object $pager
     * @access public
     * @return object
     */
    public function getStakeholderIssue($owner = '', $activityID = 0, $pager = null)
    {
        $issueList = $this->dao->select('*')->from(TABLE_ISSUE)
            ->where('deleted')->eq('0')
            ->beginIF($owner)->andWhere('owner')->eq($owner)->fi()
            ->beginIF($activityID)->andWhere('activity')->eq($activityID)->fi()
            ->orderBy('id_desc')
            ->page($pager)
            ->fetchAll();

        return $issueList;
    }

    /**
     * Get a issue details.
     *
     * @param  int    $issueID
     * @access public
     * @return object
     */
    public function getByID($issueID)
    {
        return $this->dao->select('*')->from(TABLE_ISSUE)->where('id')->eq($issueID)->andWhere('deleted')->eq('0')->fetch();
    }

    /**
     * Get issue list data.
     *
     * @param  string    $browseType bySearch|open|assignTo|closed|suspended|canceled
     * @param  int       $queryID
     * @param  string    $orderBy
     * @param  object    $pager
     * @access public
     * @return object
     */
    public function getList($browseType = 'all', $queryID = 0, $orderBy = 'id_desc', $pager = null)
    {
        $issueQuery = '';
        if($browseType == 'bysearch')
        {
            $query = $queryID ? $this->loadModel('search')->getQuery($queryID) : '';
            if($query)
            {
                $this->session->set('issueQuery', $query->sql);
                $this->session->set('issueForm', $query->form);
            }
            if($this->session->issueQuery == false) $this->session->set('issueQuery', ' 1=1');
            $issueQuery = $this->session->issueQuery;
        }

        $issueList = $this->dao->select('*')->from(TABLE_ISSUE)
            ->where('program')->eq($this->session->program)
            ->andWhere('deleted')->eq('0')
            ->beginIF($browseType == 'open')->andWhere('status')->eq('active')->fi()
            ->beginIF($browseType == 'assignto')->andWhere('assignedTo')->eq($this->app->user->account)->fi()
            ->beginIF($browseType == 'closed')->andWhere('status')->eq('closed')->fi()
            ->beginIF($browseType == 'suspended')->andWhere('status')->eq('suspended')->fi()
            ->beginIF($browseType == 'canceled')->andWhere('status')->eq('canceled')->fi()
            ->beginIF($browseType == 'bysearch')->andWhere($issueQuery)->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();

        return $issueList;
    }

    /**
     * Get the issue in the block.
     *
     * @param  string $browseType open|assignto|closed|suspended|canceled
     * @param  int    $limit
     * @param  string $orderBy
     * @access public
     * @return array
     */
    public function getBlockIssues($browseType = 'all', $limit = 15, $orderBy = 'id_desc')
    {
        $issueList = $this->dao->select('*')->from(TABLE_ISSUE)
            ->where('program')->eq($this->session->program)
            ->andWhere('deleted')->eq('0')
            ->beginIF($browseType == 'open')->andWhere('status')->eq('active')->fi()
            ->beginIF($browseType == 'assignto')->andWhere('assignedTo')->eq($this->app->user->account)->fi()
            ->beginIF($browseType == 'closed')->andWhere('status')->eq('closed')->fi()
            ->beginIF($browseType == 'suspended')->andWhere('status')->eq('suspended')->fi()
            ->beginIF($browseType == 'canceled')->andWhere('status')->eq('canceled')->fi()
            ->orderBy($orderBy)
            ->limit($limit)
            ->fetchAll();

        return $issueList;
    }

    /**
     * Get activity list.
     *
     * @access public
     * @return object
     */
    public function getActivityPairs()
    {
        return $this->dao->select('id,name')->from(TABLE_ACTIVITY)->where('deleted')->eq('0')->orderBy('id_desc')->fetchPairs();
    }

    /**
     * Create an issue.
     *
     * @access public
     * @return bool
     */
    public function create()
    {
        $now  = helper::now();
        $data = fixer::input('post')
            ->add('createdBy', $this->app->user->account)
            ->add('createdDate', $now)
            ->add('program', $this->session->program)
            ->remove('labels,files')
            ->addIF($this->post->assignedTo, 'assignedBy', $this->app->user->account)
            ->addIF($this->post->assignedTo, 'assignedDate', $now)
            ->stripTags($this->config->issue->editor->create['id'], $this->config->allowedTags)
            ->get();

        $this->dao->insert(TABLE_ISSUE)->data($data)->batchCheck($this->config->issue->create->requiredFields, 'notempty')->exec();
        $issueID = $this->dao->lastInsertID();
        $this->loadModel('file')->saveUpload('issue', $issueID);

        return $issueID;
    }

    /**
     * Update an issue.
     *
     * @param  int    $issueID
     * @access public
     * @return bool
     */
    public function update($issueID)
    {
        $oldIssue = $this->getByID($issueID);

        $now = helper::now();
        $data = fixer::input('post')
            ->add('editedBy', $this->app->user->account)
            ->add('editedDate', $now)
            ->addIF($this->post->assignedTo, 'assignedBy', $this->app->user->account)
            ->addIF($this->post->assignedTo, 'assignedDate', $now)
            ->stripTags($this->config->issue->editor->edit['id'], $this->config->allowedTags)
            ->get();

        $this->dao->update(TABLE_ISSUE)->data($data)
            ->where('id')->eq($issueID)
            ->batchCheck($this->config->issue->edit->requiredFields, 'notempty')
            ->exec();

        return common::createChanges($oldIssue, $data);
    }

    /**
     * Update assignor.
     *
     * @param  int    $issueID
     * @access public
     * @return bool
     */
    public function assignTo($issueID)
    {
        $oldIssue = $this->getByID($issueID);
        $data = fixer::input('post')
            ->add('assignedBy', $this->app->user->account)
            ->add('assignedDate', helper::now())
            ->get();

        $this->dao->update(TABLE_ISSUE)->data($data)->where('id')->eq($issueID)->exec();

        return common::createChanges($oldIssue, $data);
    }

    /**
     * Close an issue.
     *
     * @param  int    $issueID
     * @access public
     * @return bool
     */
    public function close($issueID)
    {
        $oldIssue = $this->getByID($issueID);
        $data = fixer::input('post')
            ->add('closeBy', $this->app->user->account)
            ->add('status', 'closed')
            ->get();

        $this->dao->update(TABLE_ISSUE)->data($data)->where('id')->eq($issueID)->exec();

        return common::createChanges($oldIssue, $data);
    }

    /**
     * Cancel an issue.
     *
     * @param  int    $issueID
     * @access public
     * @return bool
     */
    public function cancel($issueID)
    {
        $oldIssue = $this->getByID($issueID);
        $data     = fixer::input('post')->get();
        $this->dao->update(TABLE_ISSUE)->data($data)->where('id')->eq($issueID)->exec();

        return common::createChanges($oldIssue, $data);
    }

    /**
     * Activate an issue.
     *
     * @param  int    $issueID
     * @access public
     * @return bool
     */
    public function activate($issueID)
    {
        $oldIssue = $this->getByID($issueID);
        $data = fixer::input('post')
            ->add('status', 'active')
            ->get();

        $this->dao->update(TABLE_ISSUE)->data($data)->where('id')->eq($issueID)->exec();

        return common::createChanges($oldIssue, $data);
    }

    /**
     * Batch create issue.
     *
     * @access public
     * @return void
     */
    public function batchCreate()
    {
        $now  = helper::now();
        $data = fixer::input('post')->get();

        $issues = array();
        foreach($data->dataList as $issue)
        {
            if(!trim($issue['title'])) continue;

            $issue['createdBy']   = $this->app->user->account;
            $issue['createdDate'] = $now;
            $issue['program']     = $this->session->program;
            if($issue['assignedTo'])
            {
                $issue['assignedBy']   = $this->app->user->account;
                $issue['assignedDate'] = $now;
            }

            foreach(explode(',', $this->config->issue->create->requiredFields) as $field)
            {
                $field = trim($field);
                if($field and empty($issue[$field])) return dao::$errors['message'][] = sprintf($this->lang->error->notempty, $this->lang->issue->$field);
            }

            $issues[] = $issue;
        }
        foreach($issues as $issue) $this->dao->insert(TABLE_ISSUE)->data($issue)->exec();

        return true;
    }

    /**
     * Resolve an issue.
     *
     * @param  int    $issueID
     * @access public
     * @return object
     */
    public function resolve($issueID)
    {
        $issue = $this->post->issue;
        $issue['status'] = 'resolved';
        $this->dao->update(TABLE_ISSUE)->data($issue)->where('id')->eq($issueID)->exec();
    }

    /**
     * Create an task.
     *
     * @access public
     * @return object
     */
    public function createTask()
    {
        $task = fixer::input('post')->remove('issue,spec')->get();
        $this->dao->insert(TABLE_TASK)->data($task, 'teamMember,storyEstimate,storyDesc,storyPri,labels,files')->exec();
        return $this->dao->lastInsertID();
    }

    /**
     * Create a story.
     *
     * @access public
     * @return int
     */
    public function createStory()
    {
        $story = fixer::input('post')->remove('issue,color')
            ->setIF($this->post->needNotReview or $this->post->projectID > 0, 'status', 'active')
            ->get();

        $this->dao->insert(TABLE_STORY)->data($story, 'teamMember,storyEstimate,storyDesc,storyPri,labels,files,spec,story,needNotReview')->exec();

        $id = $this->dao->lastInsertID();
        $this->dao->insert(TABLE_STORYSPEC)
            ->set('story')->eq($id)
            ->set('title')->eq($story->title)
            ->set('spec')->eq($story->spec)
            ->set('version')->eq(1)
            ->exec();

        return $id;
    }

    /**
     * Create a bug.
     *
     * @access public
     * @return int
     */
    public function createBug()
    {
        $bug = fixer::input('post')->remove('issue,spec,color')->join('openedBuild', ',')->get();
        $this->dao->insert(TABLE_BUG)->data($bug, 'teamMember,storyEstimate,storyDesc,storyPri,labels,files')->exec();
        return $this->dao->lastInsertID();
    }

    /**
     * Create a risk.
     *
     * @access public
     * @return int
     */
    public function createRisk()
    {
        $risk = fixer::input('post')->remove('issue,color,estimate')->get();
        $this->dao->insert(TABLE_RISK)->data($risk, 'spec,title,teamMember,storyEstimate,storyDesc,storyPri,labels,files')->exec();
        return $this->dao->lastInsertID();
    }

   /**
     * Build issue search form.
     *
     * @param  string $actionURL
     * @param  int    $queryID
     * @access public
     * @return void
     */
    public function buildSearchForm($actionURL, $queryID)
    {
        $this->config->issue->search['actionURL'] = $actionURL;
        $this->config->issue->search['queryID']   = $queryID;

        $this->loadModel('search')->setSearchParams($this->config->issue->search);
    }
}
