#!/usr/bin/env php
<?php
include dirname(dirname(dirname(__FILE__))) . '/lib/init.php';
su('admin');

/**

title=测试 programModel::getTopPairs();
cid=1
pid=1

查看id=1的父项目集 >> 项目集1
查看父项目集的个数 >> 6

*/

global $tester;
$tester->loadModel('program');

r(count($tester->program->getTopPairs()))               && p() && e('10'); // 获取系统中所有顶级项目集数量
r(count($tester->program->getTopPairs('', 'noclosed'))) && p() && e('10'); // 获取系统中所有未关闭的顶级项目集数量
r(count($tester->program->getTopPairs('', '', true)))   && p() && e('10'); // 获取系统中所有顶级项目集数量
