<?php
class riskModel extends model
{
    /**
     * Create a bug.
     *
     * @access public
     * @return int|bool
     */
    public function create()
    {
        $risk = fixer::input('post')
            ->add('program', $this->session->program)
            ->add('createdBy', $this->app->user->account)
            ->add('createdDate', helper::today())
            ->stripTags($this->config->risk->editor->create['id'], $this->config->allowedTags)
            ->remove('uid')
            ->get();

        $risk = $this->loadModel('file')->processImgURL($risk, $this->config->risk->editor->create['id'], $this->post->uid);
        $this->dao->insert(TABLE_RISK)->data($risk)->autoCheck()->batchCheck($this->config->risk->create->requiredFields, 'notempty')->exec();

        if(!dao::isError()) return $this->dao->lastInsertID();
        return false;
    }

    /**
     * Batch create risk.
     *
     * @access public
     * @return bool
     */
    public function batchCreate()
    {
        $data = fixer::input('post')->get(); 

        $this->loadModel('action');
        foreach($data->name as $i => $name)
        {
            if(!$name) continue; 

            $risk = new stdclass();
            $risk->name        = $name;
            $risk->source      = $data->source[$i];
            $risk->category    = $data->category[$i];
            $risk->strategy    = $data->strategy[$i];
            $risk->program     = $this->session->program;
            $risk->createdBy   = $this->app->user->account;
            $risk->createdDate = helper::today();

            $this->dao->insert(TABLE_RISK)->data($risk)->autoCheck()->exec();

            $riskID = $this->dao->lastInsertID();
            $this->action->create('risk', $riskID, 'Opened');
        }

        return true;
    }

    /**
     * Update a risk.
     *
     * @param  int    $riskID
     * @access public
     * @return array|bool
     */
    public function update($riskID)
    {
        $oldRisk = $this->dao->select('*')->from(TABLE_RISK)->where('id')->eq((int)$riskID)->fetch();

        $risk = fixer::input('post')
            ->add('editedBy', $this->app->user->account)
            ->add('editedDate', helper::today())
            ->stripTags($this->config->risk->editor->edit['id'], $this->config->allowedTags)
            ->remove('uid')
            ->get();

        $this->dao->update(TABLE_RISK)->data($risk)->autoCheck()->where('id')->eq((int)$riskID)->exec();

        if(!dao::isError()) return common::createChanges($oldRisk, $risk);
        return false;
    }

    /**
     * Track a risk.
     *
     * @param  int    $riskID
     * @access public
     * @return array|bool
     */
    public function track($riskID)
    {
        $oldRisk = $this->dao->select('*')->from(TABLE_RISK)->where('id')->eq((int)$riskID)->fetch();

        $risk = fixer::input('post')
            ->add('editedBy', $this->app->user->account)
            ->add('editedDate', helper::today())
            ->stripTags($this->config->risk->editor->track['id'], $this->config->allowedTags)
            ->remove('isChange,comment,uid,files,label')
            ->get();

        $this->dao->update(TABLE_RISK)->data($risk)->autoCheck()->where('id')->eq((int)$riskID)->exec();

        if(!dao::isError()) return common::createChanges($oldRisk, $risk);
        return false;
    }

    /**
     * Get risks List.
     *
     * @param  string $browseType
     * @param  string $param
     * @param  string $orderBy
     * @param  int    $pager
     * @access public
     * @return object
     */
    public function getList($browseType = '', $param = '', $orderBy = 'id_desc', $pager = null)
    {
        if($browseType == 'bySearch') return $this->getBySearch($param, $orderBy, $pager);

        return $this->dao->select('*')->from(TABLE_RISK)
            ->where('deleted')->eq(0)
            ->beginIF($browseType != 'all' and $browseType != 'assignTo')->andWhere('status')->eq($browseType)->fi()
            ->beginIF($browseType == 'assignTo')->andWhere('assignedTo')->eq($this->app->user->account)->fi()
            ->andWhere('program')->eq($this->session->program)
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');
    }

    /**
     * Get risks by search
     *
     * @param  string $queryID
     * @param  string $orderBy
     * @param  int    $pager
     * @access public
     * @return object
     */
    public function getBySearch($queryID = '', $orderBy = 'id_desc', $pager = null)
    {
        if($queryID && $queryID != 'myQueryID')
        {
            $query = $this->loadModel('search')->getQuery($queryID);
            if($query)
            {
                $this->session->set('riskQuery', $query->sql);
                $this->session->set('riskForm', $query->form);
            }
            else
            {
                $this->session->set('riskQuery', ' 1 = 1');
            }
        }
        else
        {
            if($this->session->riskQuery == false) $this->session->set('riskQuery', ' 1 = 1');
        }

        $riskQuery = $this->session->riskQuery;

        return $this->dao->select('*')->from(TABLE_RISK)
            ->where($riskQuery)
            ->andWhere('deleted')->eq('0')
            ->andWhere('program')->eq($this->session->program)
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('id');
    }

    /**
     * Get risks of pairs
     *
     * @access public
     * @return object
     */
    public function getPairs()
    {
        return $this->dao->select('id, name')->from(TABLE_RISK)
            ->where('deleted')->eq(0)
            ->andWhere('program')->eq($this->session->program)
            ->fetchPairs();
    }

    /**
     * Get risk by ID
     *
     * @param  int    $riskID
     * @access public
     * @return object
     */
    public function getByID($riskID)
    {
        return $this->dao->select('*')->from(TABLE_RISK)->where('id')->eq((int)$riskID)->fetch();
    }

    /**
     * Print assignedTo html
     *
     * @param  int    $risk
     * @param  int    $users
     * @access public
     * @return string
     */
    public function printAssignedHtml($risk, $users)
    {
        $btnTextClass   = '';
        $assignedToText = zget($users, $risk->assignedTo);

        if(empty($risk->assignedTo))
        {
            $btnTextClass   = 'text-primary';
            $assignedToText = $this->lang->risk->noAssigned;
        }
        if($risk->assignedTo == $this->app->user->account) $btnTextClass = 'text-red';

        $btnClass     = $risk->assignedTo == 'closed' ? ' disabled' : '';
        $btnClass     = "iframe btn btn-icon-left btn-sm {$btnClass}";
        $assignToLink = helper::createLink('risk', 'assignTo', "riskID=$risk->id", '', true);
        $assignToHtml = html::a($assignToLink, "<i class='icon icon-hand-right'></i> <span title='" . zget($users, $risk->assignedTo) . "' class='{$btnTextClass}'>{$assignedToText}</span>", '', "class='$btnClass'");

        echo !common::hasPriv('risk', 'assignTo', $risk) ? "<span style='padding-left: 21px' class='{$btnTextClass}'>{$assignedToText}</span>" : $assignToHtml;
    }

    /**
     * Assign a risk.
     *
     * @param  int    $riskID
     * @access public
     * @return array|bool
     */
    public function assign($riskID)
    {
        $oldRisk = $this->getByID($riskID);
        
        $risk = fixer::input('post')
            ->add('editedBy', $this->app->user->account)
            ->add('editedDate', helper::today())
            ->setDefault('assignedDate', helper::today())
            ->stripTags($this->config->risk->editor->assignto['id'], $this->config->allowedTags)
            ->remove('uid,comment,files,label')
            ->get();

        $this->dao->update(TABLE_RISK)->data($risk)->autoCheck()->where('id')->eq((int)$riskID)->exec();

        if(!dao::isError()) return common::createChanges($oldRisk, $risk);
        return false;
    }

    /**
     * Cancel a risk.
     *
     * @param  int    $riskID
     * @access public
     * @return array|bool
     */
    public function cancel($riskID)
    {
        $oldRisk = $this->getByID($riskID);
        
        $risk = fixer::input('post')
            ->setDefault('status','canceled')
            ->add('editedBy', $this->app->user->account)
            ->add('editedDate', helper::today())
            ->stripTags($this->config->risk->editor->cancel['id'], $this->config->allowedTags)
            ->remove('uid,comment')
            ->get();

        $this->dao->update(TABLE_RISK)->data($risk)->autoCheck()->where('id')->eq((int)$riskID)->exec();

        if(!dao::isError()) return common::createChanges($oldRisk, $risk);
        return false;
    }

    /**
     * Close a risk.
     *
     * @param  int    $riskID
     * @access public
     * @return array|bool
     */
    public function close($riskID)
    {
        $oldRisk = $this->getByID($riskID);
        
        $risk = fixer::input('post')
            ->setDefault('status','closed')
            ->add('editedBy', $this->app->user->account)
            ->add('editedDate', helper::today())
            ->stripTags($this->config->risk->editor->close['id'], $this->config->allowedTags)
            ->remove('uid,comment')
            ->get();

        $this->dao->update(TABLE_RISK)->data($risk)->autoCheck()->where('id')->eq((int)$riskID)->exec();

        if(!dao::isError()) return common::createChanges($oldRisk, $risk);
        return false;
    }

    /**
     * Hangup a risk.
     *
     * @param  int    $riskID
     * @access public
     * @return array|bool
     */
    public function hangup($riskID)
    {
        $oldRisk = $this->getByID($riskID);
        
        $risk = fixer::input('post')
            ->setDefault('status','hangup')
            ->add('editedBy', $this->app->user->account)
            ->add('editedDate', helper::today())
            ->get();

        $this->dao->update(TABLE_RISK)->data($risk)->autoCheck()->where('id')->eq((int)$riskID)->exec();

        if(!dao::isError()) return common::createChanges($oldRisk, $risk);
        return false;
    }

    /**
     * Activate a risk.
     *
     * @param  int    $riskID
     * @access public
     * @return array|bool
     */
    public function activate($riskID)
    {
        $oldRisk = $this->getByID($riskID);
        
        $risk = fixer::input('post')
            ->setDefault('status','active')
            ->add('editedBy', $this->app->user->account)
            ->add('editedDate', helper::today())
            ->get();

        $this->dao->update(TABLE_RISK)->data($risk)->autoCheck()->where('id')->eq((int)$riskID)->exec();

        if(!dao::isError()) return common::createChanges($oldRisk, $risk);
        return false;
    }

    /**
     * Adjust the action is clickable.
     *
     * @param  int    $risk
     * @param  int    $action
     * @static
     * @access public
     * @return bool
     */
    public static function isClickable($risk, $action)
    {
        $action = strtolower($action);

        if($action == 'cancel' or $action == 'close') return $risk->status != 'canceled' and $risk->status != 'closed';
        if($action == 'hangup')   return $risk->status == 'active';
        if($action == 'activate') return $risk->status != 'active';

        return true;
    }

    /**
     * Build search form.
     *
     * @param  int    $queryID
     * @param  string $actionURL
     * @access public
     * @return void
     */
    public function buildSearchForm($queryID, $actionURL)
    {
        $this->config->risk->search['actionURL'] = $actionURL;
        $this->config->risk->search['queryID']   = $queryID;
        
        $this->loadModel('search')->setSearchParams($this->config->risk->search);
    }
}
