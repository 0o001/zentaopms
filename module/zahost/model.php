<?php
/**
 * The model file of zahost module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Wang Jianhua <wangjiahua@easycorp.ltd>
 * @package     zahost
 * @version     $Id$
 * @link        http://www.zentao.net
 */
class zahostModel extends model
{
    /**
     * Set lang;
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->app->lang->host       = $this->lang->zahost;
    }

    /**
     * Create a host.
     *
     * @access public
     * @return int|bool
     */
    public function create()
    {
        $hostInfo = fixer::input('post')
            ->setDefault('cpuNumber,cpuCores,diskSize,memory', 0)
            ->get();

        $this->dao->table      = 'zahost';
        $hostInfo->type        = 'zahost';
        $hostInfo->createdBy   = $this->app->user->account;
        $hostInfo->createdDate = helper::now();

        $this->dao->update(TABLE_ZAHOST)->data($hostInfo)
            ->batchCheck($this->config->zahost->create->requiredFields, 'notempty')
            ->batchCheck('cpuCores,diskSize', 'gt', 0)
            ->batchCheck('diskSize,memory', 'float')
            ->check('name', 'unique')
            ->autoCheck();
        if(dao::isError()) return false;

        $this->dao->insert(TABLE_ZAHOST)->data($hostInfo)->autoCheck()->exec();
        $hostID = $this->dao->lastInsertID();
        if(!dao::isError())
        {
            $this->loadModel('action')->create('zahost', $hostID, 'created');
            return $hostID;
        }

        return false;
    }

    /**
     * Update a host.
     *
     * @param  int    $hostID
     * @access public
     * @return array|bool
     */
    public function update($hostID)
    {
        $oldHost              = $this->getById($hostID);
        $hostInfo             = fixer::input('post')->get();
        $hostInfo->editedBy   = $this->app->user->account;
        $hostInfo->editedDate = helper::now();

        $this->dao->update(TABLE_ZAHOST)->data($hostInfo)
            ->batchCheck($this->config->zahost->create->requiredFields, 'notempty')
            ->batchCheck('diskSize,memory', 'float');
        if(dao::isError()) return false;

        $this->dao->update(TABLE_ZAHOST)->data($hostInfo, 'name')->autoCheck()
            ->batchCheck('cpuCores,diskSize', 'gt', 0)
            ->batchCheck('diskSize,memory', 'float')
            ->where('id')->eq($hostID)->exec();
        return common::createChanges($oldHost, $hostInfo);
    }

    /**
     * Get image by ID.
     *
     * @param  int    $imageID
     * @access public
     * @return object
     */
    public function getImageByID($imageID)
    {
        return $this->dao->select('*')->from(TABLE_IMAGE)->where('deleted')->eq(0)->andWhere('id')->eq($imageID)->fetch();
    }

    /**
     * Get image by name.
     *
     * @param  string $imageName
     * @param  int    $hostID
     * @access public
     * @return object
     */
    public function getImageByNameAndHostID($imageName, $hostID)
    {
        return $this->dao->select('*')->from(TABLE_IMAGE)
            ->where('deleted')->eq(0)
            ->andWhere('hostID')->eq($hostID)
            ->andWhere('name')->eq($imageName)->fetch();
    }

    /**
     * Get image files from ZAgent server.
     *
     * @param  object $hostID
     * @access public
     * @return array
     */
    public function getImageList($hostID, $browseType = 'all', $param = 0, $orderBy = 'id', $pager = null)
    {
        $imageList = json_decode(file_get_contents($this->config->zahost->imageListUrl));

        $downloadedImageList = $this->dao->select('*')->from(TABLE_IMAGE)
            ->where('hostID')->eq($hostID)
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll('name');

        foreach($imageList as &$image)
        {
            $downloadedImage = zget($downloadedImageList, $image->name, '');
            if(empty($downloadedImage))
            {
                $image->id     = 0;
                $image->status = '';
            }
            else
            {
                $image->id     = $downloadedImage->id;
                $image->status = $downloadedImage->status;
            }

            $image->hostID = $hostID;
        }

        return $imageList;
    }

    /**
     * create image.
     *
     * @param  int    $hostID
     * @param  string $imageName
     * @access public
     * @return object
     */
    public function createImage($hostID, $imageName)
    {
        $imageList = json_decode(file_get_contents($this->config->zahost->imageListUrl));

        $imageData = new stdclass;
        foreach($imageList  as $item) if($item->name == $imageName) $imageData = $item;

        $imageData->hostID = $hostID;
        $imageData->status = 'created';
        $imageData->osCategory = $imageData->os;
        unset($imageData->os);

        $this->dao->insert(TABLE_IMAGE)->data($imageData, 'desc')->autoCheck()->exec();
        if(dao::isError()) return false;

        $imageID = $this->dao->lastInsertID();
        $this->loadModel('action')->create('image', $imageID, 'Created');

        return $this->getImageByID($imageID);
    }

    /**
     * Send download image command to HOST.
     *
     * @param  object    $image
     * @access public
     * @return bool
     */
    public function downloadImage($image)
    {
        $host   = $this->getById($image->hostID);
        $apiUrl = 'http://' . $host->address . ':' . $this->config->zahost->defaultPort . '/api/v1/download/add';

        $apiParams['md5']  = $image->md5;
        $apiParams['url']  = $image->address;
        $apiParams['task'] = intval($image->id);

        $response = json_decode(commonModel::http($apiUrl, array($apiParams), array(CURLOPT_CUSTOMREQUEST => 'POST'), array(), 'json'));

        if($response and $response->code == 'success')
        {
            $this->dao->update(TABLE_IMAGE)
                ->set('status')->eq('created')
                ->where('id')->eq($image->id)->exec();
            return true;
        }

        dao::$errors[] = $this->lang->zahost->image->downloadImageFail;
        return false;
    }

    /**
     * Query image download progress.
     *
     * @param  object $image
     * @access public
     * @return string Return Status code.
     */
    public function queryDownloadImageStatus($image)
    {
        $host   = $this->getById($image->hostID);
        $apiUrl = 'http://' . $host->address . ':' . $this->config->zahost->defaultPort . '/api/v1/task/getStatus';

        $result = json_decode(commonModel::http($apiUrl, array(), array(CURLOPT_CUSTOMREQUEST => 'POST'), array(), 'json'));
        if(!$result or $result->code != 'success') return $image->status;

        foreach($result->data as $status => $group)
        {
            $currentTask = null;
            foreach($group as $host)
            {
                if($host->task == $image->id )
                {
                    $currentTask = $host;
                    break;
                }
            }

            if($currentTask)
            {
                $image->rate   = $currentTask->rate;
                $image->status = $status;

                $this->dao->update(TABLE_IMAGE)
                    ->set('osCategory')->eq($image->os)
                    ->set('status')->eq($status)
                    ->set('path')->eq($currentTask->path)
                    ->where('id')->eq($image->id)->exec();

                break;
            }
        }

        return $image;
    }

    /**
     * Query download image status.
     *
     * @param  object $image
     * @access public
     * @return object
     */
    public function downloadImageStatus($image)
    {
        $host      = $this->getById($image->hostID);
        $statusApi = 'http://' . $host->address . '/api/v1/task/status';

        $response = json_decode(commonModel::http($statusApi, array(), array(CURLOPT_CUSTOMREQUEST => 'GET'), array(), 'json'));

        a($response);
        if($response->code == 200) return true;

        dao::$errors[] = $response->msg;
        return false;

    }

    /**
     * Get host by id.
     *
     * @param  int    $hostID
     * @access public
     * @return object
     */
    public function getById($hostID)
    {
        return $this->dao->select('*,id as hostID')->from(TABLE_ZAHOST)
            ->where('id')->eq($hostID)
            ->fetch();
    }

    /**
     * Get pairs.
     *
     * @param  string  $idFrom
     * @access public
     * @return array
     */
    public function getPairs()
    {
        return $this->dao->select("id,name")->from(TABLE_ZAHOST)
            ->where('deleted')->eq('0')
            ->andWhere('type')->eq('zahost')
            ->orderBy('`group`')
            ->fetchPairs('id', 'name');
    }

    /**
     * Get host list.
     *
     * @param  string $browseType
     * @param  int    $param
     * @param  string $orderBy
     * @param  object $pager
     * @access public
     * @return array
     */
    public function getList($browseType = 'all', $param = 0, $orderBy = 'id_desc', $pager = null)
    {
        $query = '';
        if($browseType == 'bysearch')
        {
            /* Concatenate the conditions for the query. */
            if($param)
            {
                $query = $this->loadModel('search')->getQuery($param);
                if($query)
                {
                    $this->session->set('zahostQuery', $query->sql);
                    $this->session->set('zahostForm', $query->form);
                }
                else
                {
                    $this->session->set('zahostQuery', ' 1 = 1');
                }
            }
            else
            {
                if($this->session->zahostQuery == false) $this->session->set('zahostQuery', ' 1 = 1');
            }
            $query = $this->session->zahostQuery;
        }

        return $this->dao->select('*,id as hostID')->from(TABLE_ZAHOST)
            ->where('deleted')->eq('0')
            ->andWhere('type')->eq('zahost')
            ->beginIF($query)->andWhere($query)->fi()
            ->orderBy($orderBy)
            ->page($pager)
            ->fetchAll();
    }

    /**
     * Build test task menu.
     *
     * @param  object $host
     * @param  string $type
     * @access public
     * @return string
     */
    public function buildOperateMenu($host, $type = 'view')
    {
        $function = 'buildOperate' . ucfirst($type) . 'Menu';
        return $this->$function($host);
    }

    /**
     * Build test task view menu.
     *
     * @param  object $host
     * @access public
     * @return string
     */
    public function buildOperateViewMenu($host)
    {
        if($host->deleted) return '';

        $menu   = '';
        $params = "hostID=$host->hostID";

        $menu .= $this->buildMenu('zahost', 'edit',   $params, $host, 'view');

        $params = "hostID=$host->assetID";
        $menu .= $this->buildMenu('zahost', 'delete', $params, $host, 'view', 'trash', 'hiddenwin');

        return $menu;
    }

    /**
     * Get image pairs by host.
     *
     * @param  int    $hostID
     * @access public
     * @return array
     */
    public function getImagePairs($hostID)
    {
        return $this->dao->select('id,name')->from(TABLE_IMAGE)->where('hostID')->eq($hostID)->andWhere('status')->eq('completed')->fetchPairs();
    }

    /**
     * Get service status from ZAgent server.
     *
     * @param  object $host
     * @access public
     * @return array
     */
    public function getServiceStatus($host)
    {
        $result = json_decode(commonModel::http("http://{$host->address}:8086/api/v1/service/check", json_encode(array("services" => "all"))));
        if(empty($result) || $result->code != 'success')
        {
            $result = new stdclass;
            $result->data = $this->lang->zahost->initHost->serviceStatus;
        }

        return $result->data;
    }
}
