<?php
$lang->tree->allMenu    = '所有目录';
$lang->tree->manageMenu = '维护目录';

global $app;
if($app->rawModule == 'tree' and $app->rawMethod == 'browse')
{
    $lang->tree->edit             = '编辑目录';
    $lang->tree->delete           = '删除目录';
    $lang->tree->child            = '子目录';
    $lang->tree->manageStoryChild = '维护子目录';
    $lang->tree->name             = '目录名称';
    $lang->tree->syncFromProduct  = '复制目录';
}
