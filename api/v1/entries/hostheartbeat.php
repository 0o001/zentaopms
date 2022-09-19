<?php
/**
 * The host entry point of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2022 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL(http://zpl.pub/page/zplv12.html) or AGPL(https://www.gnu.org/licenses/agpl-3.0.en.html)
 * @author      Yuchun Li <liyuchun@easycorp.ltd>
 * @package     entries
 * @version     1
 * @link        http://www.zentao.net
 */
class hostHeartbeatEntry extends baseEntry
{
    /**
     * Listen host heartbeat.
     *
     * @param  int|string $userID
     * @access public
     * @return void
     */
    public function post()
    {
        /* Check authorize. */
        $header = getallheaders();
        $token  = isset($header['Authorization']) ? substr($header['Authorization'], 7) : '';
        $secret = isset($this->requestBody->secret) ? $this->requestBody->secret : '';
        if(!$secret and !$token) return $this->sendError(401, 'Unauthorized');

        /* Check param. */
        $status = $this->requestBody->status;
        $now    = helper::now();
        if(!$status) return $this->sendError(400, 'Params error.');

        $conditionField = $secret ? 'secret' : 'token';
        $conditionValue = $secret ? $secret  : $token;
        $host = new stdclass();
        $host->status = $status;
        if($secret)
        {
            $host->token       = md5($secret . $now);
            $host->expiredDate = date('Y-m-d H:i:s', time() + 7200);
        }

        $this->dao = $this->loadModel('common')->dao;
        $assetID = $this->dao->select('assetID')->from(TABLE_HOST)
            ->beginIF($secret)->where('secret')->eq($secret)->fi()
            ->beginIF(!$secret)->where('token')->eq($token)
            ->andWhere('expiredDate')->gt($now)->fi()
            ->fetch('assetID');
        if(!$assetID) return $this->sendError(400, 'Secret error.');

        $this->dao->update(TABLE_HOST)->data($host)->where($conditionField)->eq($conditionValue)->exec();
        $this->dao->update(TABLE_ASSET)->set('registerDate')->eq($now)->where('id')->eq($assetID)->exec();

        if(!$secret) return $this->sendSuccess(200, 'success');

        $host->expiredTimeUnix = strtotime($host->expiredDate);
        unset($host->status);
        unset($host->expiredDate);
        return $this->send(200, $host);
    }
}
