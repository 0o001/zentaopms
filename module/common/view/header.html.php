<?php
if($extView = $this->getExtViewFile(__FILE__)){include $extView; return helper::cd();}
include 'header.lite.html.php';
include 'chosen.html.php';
//include 'validation.html.php';
?>
<?php if(empty($_GET['onlybody']) or $_GET['onlybody'] != 'yes'):?>
<?php $this->app->loadConfig('sso');?>
<?php if(!empty($config->sso->redirect)) js::set('ssoRedirect', $config->sso->redirect);?>
<?php $isProgram = (zget($lang->navGroup, $app->rawModule) == 'program');?>
<?php $isSystem  = (zget($lang->navGroup, $app->rawModule) == 'system');?>
<div id='menu'>
  <nav id='menuNav'><?php commonModel::printMainNav($app->rawModule);?></nav>
  <div id='menuFooter'>
    <button type='button' id='menuToggle'><i class='icon icon-sm icon-menu-collapse'></i></button>
  </div>
</div>
<header id='header'>
  <div id='mainHeader'>
    <div class='container'>
      <div id='heading'>
        <?php if($isProgram) echo $this->loadModel('program')->getSwitcher($this->program->getPairs(), $this->session->program, $app->rawModule, $app->rawMethod);?>
        <?php if($isSystem)  echo $this->loadModel('custom')->getModeSwitcher();?>
      </div>
      <nav id='navbar'><?php commonModel::printMainmenu($app->rawModule, $app->rawMethod);?></nav>
      <div id='toolbar'>
        <div id="userMenu">
          <?php common::printSearchBox();?>
          <ul id="userNav" class="nav nav-default">
            <li><?php common::printUserBar();?></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <?php if(!in_array($app->rawModule, $lang->noMenuModule)):?>
  <div id='subHeader'>
    <div class='container'>
      <div id="pageNav" class='btn-toolbar'><?php if(isset($lang->modulePageNav)) echo $lang->modulePageNav;?></div>
      <nav id='subNavbar'><?php common::printModuleMenu($app->rawModule);?></nav>
      <div id="pageActions"><div class='btn-toolbar'><?php if(isset($lang->modulePageActions)) echo $lang->modulePageActions;?></div></div>
    </div>
  </div>
<?php endif;?>
  <?php
  if(!empty($config->sso->redirect))
  {
      css::import($defaultTheme . 'bindranzhi.css');
      js::import($jsRoot . 'bindranzhi.js');
  }
  ?>
</header>

<?php endif;?>

<?php if(isset($this->config->qcVersion)):?>
<script>
$("#userMenu").append('<button class="btn btn-mini" type="button" id="showSearchGo" style="padding: 2px 3px;"><i class="icon icon-sm icon-search"></i></button>');
$("#searchbox").hide();
$("#showSearchGo").on("click", function(){$("#searchbox").show(); $("#showSearchGo").hide();});
$("#searchInput").mouseout(function()
{
    var searchValue = $("#searchInput").val();
    if(searchValue == ''){$("#searchbox").hide(); $("#showSearchGo").show()};
});
</script>
<?php endif;?>
<main id='main' <?php if(!empty($config->sso->redirect)) echo "class='ranzhiFixedTfootAction'";?> >
  <div class='container'>
