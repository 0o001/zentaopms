<?php
class blockTest
{
    public function __construct()
    {
         global $tester;
         $this->objectModel = $tester->loadModel('block');
    }

    public function saveTest($id, $source, $type, $module = 'my')
    {
        $objects = $this->objectModel->save($id, $source, $type, $module = 'my');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get block by ID.
     *
     * @param  int $blockID
     * @access public
     * @return void
     */
    public function getByIDTest($blockID)
    {
        $objects = $this->objectModel->getByID($blockID);

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get saved block config.
     *
     * @param  int    $id
     * @access public
     * @return object
     */
    public function getBlockTest($id)
    {
        $objects = $this->objectModel->getBlock($id);

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get last key.
     *
     * @param  string $appName
     * @access public
     * @return int
     */
    public function getLastKeyTest($module = 'my')
    {
        $objects = $this->objectModel->getLastKey($module);

        $objects[$module] = $objects;

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get block list for account.
     *
     * @param  string $appName
     * @access public
     * @return void
     */
    public function getBlockListTest($module = 'my', $type = '')
    {
        $objects = $this->objectModel->getBlockList($module, $type);

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get hidden blocks
     *
     * @access public
     * @return array
     */
    public function getHiddenBlocksTest($module = 'my')
    {
        $objects = $this->objectModel->getHiddenBlocks($module);

        if(dao::isError()) return dao::getError();

        if(empty($objects))
        {
            $objects['code']    = 'fail';
            $objects['message'] = '未获取到隐藏的区块';
        }

        return $objects;
    }

    public function getWelcomeBlockDataTest()
    {
        $objects = $this->objectModel->getWelcomeBlockData();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function initBlockTest($module, $type = '')
    {
        $objects = $this->objectModel->initBlock($module, $type = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get block list.
     *
     * @param  string $module
     * @param  string $dashboard
     * @param  object $model
     *
     * @access public
     * @return string
     */
    public function getAvailableBlocksTest($module = '', $dashboard = '', $model = '')
    {
        $objects = json_decode($this->objectModel->getAvailableBlocks($module, $dashboard, $model));

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getListParamsTest($module = '')
    {
        $objects = json_decode($this->objectModel->getListParams($module));

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getTodoParamsTest($module = '')
    {
        $objects = $this->objectModel->getTodoParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getTaskParamsTest($module = '')
    {
        $objects = $this->objectModel->getTaskParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get Bug Params.
     *
     * @access public
     * @return json
     */
    public function getBugParamsTest($module = '')
    {
        $objects = json_decode($this->objectModel->getBugParams($module));

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get case params.
     *
     * @access public
     * @return json
     */
    public function getCaseParamsTest($module = '')
    {
        $objects = json_decode($this->objectModel->getCaseParams($module));

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getTesttaskParamsTest($module = '')
    {
        $objects = $this->objectModel->getTesttaskParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getStoryParamsTest($module = '')
    {
        $objects = $this->objectModel->getStoryParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getPlanParamsTest()
    {
        $objects = $this->objectModel->getPlanParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getReleaseParamsTest()
    {
        $objects = $this->objectModel->getReleaseParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getProjectParamsTest()
    {
        $objects = $this->objectModel->getProjectParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getProjectTeamParamsTest()
    {
        $objects = $this->objectModel->getProjectTeamParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get Build params.
     *
     * @access public
     * @return json
     */
    public function getBuildParamsTest()
    {
        $objects = json_decode($this->objectModel->getBuildParams());

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getProductParamsTest()
    {
        $objects = $this->objectModel->getProductParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getStatisticParamsTest($module = 'product')
    {
        $objects = $this->objectModel->getStatisticParams($module = 'product');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getProductStatisticParamsTest()
    {
        $objects = $this->objectModel->getProductStatisticParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getProjectStatisticParamsTest()
    {
        $objects = $this->objectModel->getProjectStatisticParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get execution statistic params.
     *
     * @access public
     * @return void
     */
    public function getExecutionStatisticParamsTest()
    {
        $objects = json_decode($this->objectModel->getExecutionStatisticParams());

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getQaStatisticParamsTest()
    {
        $objects = $this->objectModel->getQaStatisticParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getRecentProjectParamsTest()
    {
        $objects = $this->objectModel->getRecentProjectParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getOverviewParamsTest()
    {
        $objects = $this->objectModel->getOverviewParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getWaterfallReportParamsTest()
    {
        $objects = $this->objectModel->getWaterfallReportParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getWaterfallEstimateParamsTest()
    {
        $objects = $this->objectModel->getWaterfallEstimateParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getWaterfallGanttParamsTest()
    {
        $objects = $this->objectModel->getWaterfallGanttParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getWaterfallProgressParamsTest()
    {
        $objects = $this->objectModel->getWaterfallProgressParams();

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getWaterfallIssueParamsTest($module = '')
    {
        $objects = $this->objectModel->getWaterfallIssueParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getWaterfallRiskParamsTest($module = '')
    {
        $objects = $this->objectModel->getWaterfallRiskParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get execution params.
     *
     * @access public
     * @return json
     */
    public function getExecutionParamsTest()
    {
        $objects = json_decode($this->objectModel->getExecutionParams());

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get assign to me params.
     *
     * @access public
     * @return json
     */
    public function getAssignToMeParamsTest()
    {
        $objects = json_decode($this->objectModel->getAssignToMeParams());

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    /**
     * Get closed block pairs.
     *
     * @param  string $closedBlock
     * @access public
     * @return array
     */
    public function getClosedBlockPairsTest($closedBlock)
    {
        $objects = $this->objectModel->getClosedBlockPairs($closedBlock);

        if(dao::isError()) return dao::getError();

        if(empty($objects))
        {
            $objects['code']    = 'fail';
            $objects['message'] = '未获取到关闭的区域';
        }

        return $objects;
    }

    public function appendCountParamsTest($params = '')
    {
        $objects = $this->objectModel->appendCountParams($params = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function isLongBlockTest($block)
    {
        $objects = $this->objectModel->isLongBlock($block);

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function checkAPITest($hash)
    {
        $objects = $this->objectModel->checkAPI($hash);

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getScrumTestParamsTest($module = '')
    {
        $objects = $this->objectModel->getScrumTestParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getScrumListParamsTest($module = '')
    {
        $objects = $this->objectModel->getScrumListParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getScrumOverviewParamsTest($module = '')
    {
        $objects = $this->objectModel->getScrumOverviewParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getScrumRoadMapParamsTest($module = '')
    {
        $objects = $this->objectModel->getScrumRoadMapParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getScrumProductParamsTest($module = '')
    {
        $objects = $this->objectModel->getScrumProductParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getProjectDynamicParamsTest($module = '')
    {
        $objects = $this->objectModel->getProjectDynamicParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getSprintParamsTest($module = '')
    {
        $objects = $this->objectModel->getSprintParams($module = '');

        if(dao::isError()) return dao::getError();

        return $objects;
    }

    public function getStorysEstimateHoursTest($storyID)
    {
        $objects = $this->objectModel->getStorysEstimateHours($storyID);

        if(dao::isError()) return dao::getError();

        return $objects;
    }
}
