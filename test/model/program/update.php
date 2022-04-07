#!/usr/bin/env php
<?php
include dirname(dirname(dirname(__FILE__))) . '/lib/init.php';
include dirname(dirname(dirname(__FILE__))) . '/class/program.class.php';
su('admin');

/**

title=测试 programModel::update();
cid=1
pid=1

更新id为10的项目集信息 >> 测试更新项目集十
当计划开始为空时更新项目集信息 >> 『计划开始』不能为空。
当计划完成为空时更新项目集信息 >> 『计划完成』不能为空。
当计划完成小于计划开始时 >> 『计划完成』应当大于『2020-10-10』。
项目集名称已经存在时 >> 『项目集名称』已经有『项目集1』这条记录了。如果您确定该记录已删除，请到后台-系统-数据-回收站还原。
项目集开始时间小于父项目集时 >> 父项目集的开始日期：2019-09-09，开始日期不能小于父项目集的开始日期;父项目集的完成日期：2019-09-09，完成日期不能大于父项目集的完成日期

*/

$program = new programTest();

$data = array(
    'parent' => '0',
    'name' => '测试更新项目集十',
    'begin' => '2020-10-10',
    'end' => '2022-06-01',
    'acl' => 'private',
    'budget' => '100',
    'budgetUnit' => 'CNY',
    'syncPRJUnit' => true,
    'exchangeRate' => '',
    'whitelist' => array('dev10', 'dev12')
);

$normalProgram = $data;

$emptyTitleProgram = $data;
$emptyTitleProgram['name'] = '项目集1';

$emptyBeginProgram = $data;
$emptyBeginProgram['begin'] = '';

$emptyEndProgram = $data;
$emptyEndProgram['end'] = '';

$beginGtEndProgram = $data;
$beginGtEndProgram['begin'] = '2022-07-01';

$beginLtParentProgram = $data;
$beginLtParentProgram['parent'] = '9';
$beginLtParentProgram['begin']  = '2019-01-01';

r($program->update(10, $normalProgram))        && p('name')                      && e('测试更新项目集十'); // 正常更新项目集的情况
r($program->update(10, $emptyTitleProgram))    && p('message[name]:0')           && e('『项目集名称』已经有『项目集1』这条记录了。如果您确定该记录已删除，请到后台-系统-数据-回收站还原。'); // 更新项目集名称重复时
r($program->update(10, $emptyBeginProgram))    && p('message[begin]:0')          && e('『计划开始』不能为空。'); // 当计划完成为空时更新项目集信息
r($program->update(10, $emptyEndProgram))      && p('message:end')               && e('子项目的最大完成日期：2022-05-27，父项目的完成日期不能小于子项目的最大完成日期'); //当计划完成小于计划开始时
r($program->update(10, $beginGtEndProgram))    && p('message:begin')             && e('子项目集的最小开始日期：2022-02-01，父项目集的开始日期不能大于子项目集的最小开始日期'); // 父项目集的开始日期大于子项目集的开始日期时
r($program->update(10, $beginLtParentProgram)) && p('message:begin;message:end') && e('父项目集的开始日期：2022-02-09，开始日期不能小于父项目集的开始日期;父项目集的完成日期：2022-04-16，完成日期不能大于父项目集的完成日期'); // 项目集开始、结束日期和子项目不符的情况
system("./ztest init");
