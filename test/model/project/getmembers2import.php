#!/usr/bin/env php
<?php
include dirname(dirname(dirname(__FILE__))) . '/lib/init.php';
su('admin');

/**

title=测试 projectModel->getMembers2Import();
cid=1
pid=1

*/

global $tester;
$tester->loadModel('project');

$members = $tester->project->getMembers2Import(11, array('admin'));

r(count($members)) && p()            && e('1');          // 获取id为11的项目团队成员个数,排除admin
r($members)        && p('pm92:role') && e('产品经理92'); // 获取id为11的项目团队成员的角色
