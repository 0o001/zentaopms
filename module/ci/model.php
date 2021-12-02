<?php
/**
 * The model file of ci module of ZenTaoPMS.
 * @author      Chenqi <chenqi@cnezsoft.com>
 * @package     product
 * @version     $Id: $
 * @link        http://www.zentao.net
 */

class ciModel extends model
{
    /**
     * Set menu.
     *
     * @access public
     * @return void
     */
    public function setMenu()
    {
        common::setMenuVars('devops', $this->session->repoID);
    }

    /**
     * Send a request to jenkins to check build status.
     *
     * @param  int    $compileID
     * @access public
     * @return void
     */
    public function checkCompileStatus($compileID = 0)
    {
        $compiles = $this->dao->select('compile.*, job.engine,job.pipeline, pipeline.name as jenkinsName,job.server,pipeline.url,pipeline.account,pipeline.token,pipeline.password')
            ->from(TABLE_COMPILE)->alias('compile')
            ->leftJoin(TABLE_JOB)->alias('job')->on('compile.job=job.id')
            ->leftJoin(TABLE_PIPELINE)->alias('pipeline')->on('job.server=pipeline.id')
            ->where('compile.status')->ne('success')
            ->andWhere('compile.status')->ne('failure')
            ->andWhere('compile.status')->ne('create_fail')
            ->andWhere('compile.status')->ne('timeout')
            ->andWhere('compile.status')->ne('canceled')
            ->beginIf($compileID)->andWhere('compile.id')->eq($compileID)->fi()
            ->andWhere('compile.createdDate')->gt(date(DT_DATETIME1, strtotime("-1 day")))
            ->fetchAll();

        foreach($compiles as $compile) $this->syncCompileStatus($compile);
    }

    /**
     * Sync compile status.
     *
     * @param  object $compile
     * @access public
     * @return void
     */
    public function syncCompileStatus($compile)
    {
        if($compile->times >= 3)
        {
            $this->dao->update(TABLE_COMPILE)->set('status')->eq('failure')->where('id')->eq($compile->id)->exec();
            return false;
        }

        if($compile->engine == 'gitlab') return $this->syncGitlabTaskStatus($compile);
        $jenkinsServer   = $compile->url;
        $jenkinsUser     = $compile->account;
        $jenkinsPassword = $compile->token ? $compile->token : base64_decode($compile->password);
        $userPWD         = "$jenkinsUser:$jenkinsPassword";
        $queueUrl        = sprintf('%s/queue/item/%s/api/json', $jenkinsServer, $compile->queue);

        $response = common::http($queueUrl, '', array(CURLOPT_USERPWD => $userPWD));

        if($compile->engine != 'gitlab') $this->dao->update(TABLE_COMPILE)->set('times = times + 1')->where('id')->eq($compile->id)->exec();
        if(strripos($response, "404") > -1)
        {
            $infoUrl  = sprintf("%s/job/%s/api/xml?tree=builds[id,number,result,queueId]&xpath=//build[queueId=%s]", $jenkinsServer, $compile->pipeline, $compile->queue);
            $response = common::http($infoUrl, '', array(CURLOPT_USERPWD => $userPWD));
            if($response)
            {
                $buildInfo   = simplexml_load_string($response);
                $buildNumber = strtolower($buildInfo->number);
                if(empty($buildNumber)) return false;

                $result = strtolower($buildInfo->result);
                if(empty($result)) return false;
                $this->updateBuildStatus($compile, $result);

                $logUrl   = sprintf('%s/job/%s/%s/consoleText', $jenkinsServer, $compile->pipeline, $buildNumber);
                $response = common::http($logUrl, '', array(CURLOPT_USERPWD => $userPWD));
                $this->dao->update(TABLE_COMPILE)->set('logs')->eq($response)->where('id')->eq($compile->id)->exec();
            }
        }
        else
        {
            $queueInfo = json_decode($response);
            if(!empty($queueInfo->executable))
            {
                $buildUrl  = $queueInfo->executable->url . 'api/json?pretty=true';
                $response  = common::http($buildUrl, '', array(CURLOPT_USERPWD => $userPWD));
                $buildInfo = json_decode($response);

                if($buildInfo->building)
                {
                    $this->updateBuildStatus($compile, 'building');
                }
                else
                {
                    $result = strtolower($buildInfo->result);
                    if(empty($result)) return false;
                    $this->updateBuildStatus($compile, $result);

                    $logUrl   = $buildInfo->url . 'logText/progressiveText/api/json';
                    $response = common::http($logUrl, '', array(CURLOPT_USERPWD => $userPWD));
                    $this->dao->update(TABLE_COMPILE)->set('logs')->eq($response)->where('id')->eq($compile->id)->exec();
                }
            }
        }
    }

    /**
     * Sync gitlab task status.
     *
     * @param  object    $compile
     * @access public
     * @return void
     */
    public function syncGitlabTaskStatus($compile)
    {
        $this->loadModel('gitlab');

        $now = helper::now();

        /* The value of `$compile->pipeline` is like `'{"project":"46","reference":"master"}'` in current design. */
        $pipeline = json_decode($compile->pipeline);
        $compile->project = isset($pipeline->project) ? $pipeline->project : $compile->pipeline;

        $pipeline = $this->gitlab->apiGetSinglePipeline($compile->server, $compile->project, $compile->queue);
        if(!isset($pipeline->id) or isset($pipeline->message)) /* The pipeline is not available. */
        {
            $pipeline->status = 'create_fail'; /* Set the status to fail. */
            $this->dao->update(TABLE_JOB)->set('lastExec')->eq($now)->set('lastStatus')->eq($pipeline->status)->where('id')->eq($compile->job)->exec();
            return false;
        }

        $jobs = $this->gitlab->apiGetJobs($compile->server, $compile->project, $compile->queue);
        $data = new stdclass;
        $data->status     = $pipeline->status;
        $data->updateDate = $now;

        foreach($jobs as $job)
        {
            if(empty($job->duration) or $job->duration == '') $job->duration = '-';
            $data->logs  = "<font style='font-weight:bold'>&gt;&gt;&gt; Job: $job->name, Stage: $job->stage, Status: $job->status, Duration: $job->duration Sec\r\n </font>";
            $data->logs .= "Job URL: <a href=\"$job->web_url\" target='_blank'>$job->web_url</a> \r\n";
            $data->logs .= $this->transformAnsiToHtml($this->gitlab->apiGetJobLog($compile->server, $compile->project, $job->id));
        }

        $this->dao->update(TABLE_COMPILE)->data($data)->where('id')->eq($compile->id)->exec();
        $this->dao->update(TABLE_JOB)->set('lastExec')->eq($now)->set('lastStatus')->eq($pipeline->status)->where('id')->eq($compile->job)->exec();

        /* Send mr message by compile status. */
        $relateMR = $this->dao->select('*')->from(TABLE_MR)->where('compileID')->eq($compile->id)->fetch();
        if($relateMR)
        {
            if($data->status == 'success') $this->loadModel('action')->create('mr', $relateMR->id, 'compilePass');
            if($data->status == 'failed')  $this->loadModel('action')->create('mr', $relateMR->id, 'compileFail');
        }
    }

    /**
     * Transform ansi text to html.
     *
     * @param  string $text
     * @access public
     * @return string
     */
    public function transformAnsiToHtml($text)
    {
        $text = preg_replace("/\x1B\[31;40m/", '<font style="color: red">',       $text);
        $text = preg_replace("/\x1B\[32;1m/",  '<font style="color: green">',     $text);
        $text = preg_replace("/\x1B\[32;1m/",  '<font style="color: green">',     $text);
        $text = preg_replace("/\x1B\[36;1m/",  '<font style="color: cyan">',      $text);
        $text = preg_replace("/\x1B\[0;33m/",  '<font style="color: yellow">',    $text);
        $text = preg_replace("/\x1B\[1m/",     '<font style="font-weight:bold">', $text);
        $text = preg_replace("/\x1B\[0;m/",    '</font><br>', $text);
        $text = preg_replace("/\x1B\[0K/",     '<br>', $text);
        return $text;
    }

    /**
     * Update ci build status.
     *
     * @param  object $build
     * @param  string $status
     * @access public
     * @return void
     */
    public function updateBuildStatus($build, $status)
    {
        $this->dao->update(TABLE_COMPILE)->set('status')->eq($status)->where('id')->eq($build->id)->exec();
        $this->dao->update(TABLE_JOB)->set('lastExec')->eq(helper::now())->set('lastStatus')->eq($status)->where('id')->eq($build->job)->exec();
    }

    /**
     * Send request.
     *
     * @param  string $url
     * @param  object $data
     * @param  string $userPWD
     * @access public
     * @return int
     */
    public function sendRequest($url, $data, $userPWD = '')
    {
        if(!empty($data->PARAM_TAG)) $data->PARAM_REVISION = '';
        $response = common::http($url, $data, array(CURLOPT_HEADER => true, CURLOPT_USERPWD => $userPWD));
        if(preg_match("!Location: .*item/(.*)/!", $response, $matches)) return $matches[1];
        return 0;
    }
}
