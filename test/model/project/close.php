#!/usr/bin/env php
<?php
include dirname(dirname(dirname(__FILE__))) . '/lib/init.php';
su('admin');

/**

title=测试 projectModel->close();
cid=1
pid=1

关闭id为20状态不是closed的项目 >> 1
关闭id为26状态是closed的项目 >> 0
关闭id为41状态是suspended的项目 >> 0

*/

global $tester;
$tester->loadModel('project');

$_POST['realEnd'] = '2022-03-11';

$changes1 = $tester->project->close(19);
$changes2 = $tester->project->close(26);
$changes3 = $tester->project->close(33);

r($changes1) && p('1:field,old,new') && e('status,wait,closed');      // 关闭id为20状态不是closed的项目
r($changes2) && p('1:field,old,new') && e('closedBy,,admin');         // 关闭id为26状态是closed的项目
r($changes3) && p('1:field,old,new') && e('status,suspended,closed'); // 关闭id为41状态是suspended的项目
system("./ztest init");
