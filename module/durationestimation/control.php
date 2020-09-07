<?php
/**
 * The control file of durationestimation of ZentaoPMS.
 *
 * @copyright   Copyright 2009-2010 QingDao Nature Easy Soft Network Technology Co,LTD (www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv11.html)
 * @author      Xiying Guan <guanxiying@xirangit.com>
 * @package     durationestimation
 * @version     $Id$
 * @link        http://www.zentao.net
 */
class durationestimation extends control
{
    public function index($programID)
    {
        $workestimation = $this->loadModel('workestimation')->getBudget($programID);
        if(!$workestimation) 
        {
            echo js::alert($this->lang->durationestimation->setWorkestimation);
            die(js::locate($this->createLink('workestimation', 'index', "currentProgram=$programID")));
        }

        $this->loadModel('programplan');
        $program = $this->loadModel('project')->getById($programID);
        $stages  = $this->loadModel('stage')->getStages();
        $title   = $this->lang->durationestimation->common . $this->lang->colon . $program->name;

        $this->view->estimationList = $this->durationestimation->getListByProgram($programID);
        if(empty($this->view->estimationList)) $this->locate(inlink('create', "currentProgram=$programID"));
        $this->view->workestimation = $workestimation;

        $this->view->title    = $title;
        $this->view->program  = $program;
        $this->view->stages   = $stages;

        $this->display();
    }

    public function create($programID = 0)
    {
        $workestimation = $this->loadModel('workestimation')->getBudget($programID);
        if(!$workestimation) 
        {
            echo js::alert($this->lang->durationestimation->setWorkestimation);
            die(js::locate($this->createLink('workestimation', 'index', "currentProgram=$programID")));
        }

        $this->loadModel('programplan');
        if(!empty($_POST))
        {
            $total = 0;
            foreach($this->post->workload as $value) $total += $value;

            $workloadTotal = $this->config->durationestimation->workloadTotal;
            if($total != $workloadTotal) $this->send(array('result' => 'fail', 'message' => $this->lang->durationestimation->workloadError));

            $this->durationestimation->save($programID);

            if(dao::isError()) $this->send(array('result' => 'fail', 'message' => dao::getError()));
            $this->send(array('result' => 'success', 'message' => $this->lang->saveSuccess, 'locate' => inlink('index', "currentProgram={$programID}")));
        }

        $this->view->estimationList = $this->durationestimation->getListByProgram($programID);
        $this->view->workestimation = $workestimation;

        $program = $this->loadModel('project')->getById($programID);
        $title   = $this->lang->durationestimation->common . $this->lang->colon . $program->name;

        $this->view->title    = $title;
        $this->view->program  = $program;
        $this->view->stages	  = $this->loadModel('stage')->getStages();

        $this->display();
    }

    /**
     * ajaxGetDuration
     * 
     * @param  int    $program 
     * @param  int    $workload 
     * @param  int    $worktimeRate 
     * @param  int    $people 
     * @access public
     * @return void
     */
    public function ajaxGetDuration($program, $stage, $workload, $worktimeRate, $people, $startDate)
    {
        $startDate = str_replace('_', '-', $startDate);
        if($startDate == '0000-00-00') $this->send(array('result' => 'success', 'endDate' => '0000-00-00'));

        $estimation = $this->loadModel('workestimation')->getBudget($program);
        $duration   = $estimation->duration * $workload / 100;
        $divisor    = ($people == 0 || $estimation->dayHour == 0) ? 0 : $worktimeRate / 100 * $people / $estimation->dayHour;
        $duration   = !$divisor ? 0 : $duration / $divisor;
        if(!$divisor) $this->send(array('result' => 'fail'));

        $holidays   = array();
        $workDays   = array();
        $i = 0;
        $this->loadModel('project');

        $startedTime = strtotime($startDate);
        $days = 0;
        for($i = 0; $days < $duration; $i ++)
        {
            $day = date('N', strtotime("+ $i days", $startedTime));
            if($this->config->project->weekend == 2)
            {
                if($day > 5) continue;
            }
            if($this->config->project->weekend == 1)
            {
                if($day > 6) continue;
            }
            $lastDay = date('Y-m-d', strtotime("+ $i days", $startedTime));
            $days ++;
        }

        $this->send(array('result' => 'success', 'endDate' => $lastDay));
    }
}
