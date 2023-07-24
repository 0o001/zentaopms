<?php
$config->ai->openai = new stdclass();

$config->ai->openai->api = new stdclass();
$config->ai->openai->api->version    = 'v1';                           // OpenAI API version, required.
$config->ai->openai->api->format     = 'https://api.openai.com/%s/%s'; // OpenAI API format, args: API version, API name.
$config->ai->openai->api->authFormat = 'Authorization: Bearer %s';     // OpenAI API auth header format.
$config->ai->openai->api->methods    = array('function' => 'chat/completions', 'chat' => 'chat/completions', 'completion' => 'completions', 'edit' => 'edits');

$config->ai->openai->params = new stdclass();
$config->ai->openai->params->chat       = new stdclass();
$config->ai->openai->params->function   = new stdclass();
$config->ai->openai->params->completion = new stdclass();
$config->ai->openai->params->edit       = new stdclass();
$config->ai->openai->params->chat->required = array('messages');
$config->ai->openai->params->chat->optional = array('max_tokens', 'temperature', 'top_p', 'n', 'stream', 'stop', 'presence_penalty', 'frequency_penalty', 'logit_bias', 'user');
$config->ai->openai->params->function->required = array('messages', 'functions', 'function_call');
$config->ai->openai->params->function->optional = array('max_tokens', 'temperature', 'top_p', 'n', 'stream', 'stop', 'presence_penalty', 'frequency_penalty', 'logit_bias', 'user');
$config->ai->openai->params->completion->required = array('prompt', 'max_tokens');
$config->ai->openai->params->completion->optional = array('suffix', 'temperature', 'top_p', 'n', 'stream', 'logprobs', 'echo', 'stop', 'presence_penalty', 'frequency_penalty', 'best_of', 'logit_bias', 'user');
$config->ai->openai->params->edit->required = array('input', 'instruction');
$config->ai->openai->params->edit->optional = array('temperature', 'top_p', 'n');

$config->ai->openai->model = new stdclass();
$config->ai->openai->model->chat       = 'gpt-3.5-turbo';
$config->ai->openai->model->function   = 'gpt-3.5-turbo-0613';
$config->ai->openai->model->completion = 'text-davinci-003';
$config->ai->openai->model->edit       = 'text-davinci-edit-001';

$config->ai->openai->contentTypeMapping = array('Content-Type: application/json' => array('', 'function', 'chat', 'completion', 'edit'), 'Content-Type: multipart/form-data' => array());
$config->ai->openai->contentType = array();
foreach($config->ai->openai->contentTypeMapping as $contentType => $apis)
{
    foreach($apis as $api) $config->ai->openai->contentType[$api] = $contentType;
}

$config->ai->createprompt = new stdclass();
$config->ai->createprompt->requiredFields = 'name';

$config->ai->testPrompt = new stdclass();
$config->ai->testPrompt->requiredFields = 'name,module,source,purpose,targetForm';

/* Data source object props definations. */
$config->ai->dataSource = array();
$config->ai->dataSource['my']['efforts'] = array('date', 'work', 'account', 'consumed', 'left', 'objectID', 'product', 'project', 'execution');

$config->ai->dataSource['product']['product'] = array('name', 'desc');
$config->ai->dataSource['product']['modules'] = array('name', 'modules');

$config->ai->dataSource['productplan']['productplan'] = array('title', 'desc', 'begin', 'end');
$config->ai->dataSource['productplan']['stories']     = array('title', 'module', 'pri', 'estimate', 'status', 'stage');
$config->ai->dataSource['productplan']['bugs']        = array('title', 'pri', 'status');

$config->ai->dataSource['release']['release'] = array('product', 'name', 'desc', 'date');
$config->ai->dataSource['release']['stories'] = array('title', 'estimate');
$config->ai->dataSource['release']['bugs']    = array('title');

$config->ai->dataSource['project']['project'] = array('name', 'type', 'desc', 'begin', 'end', 'estimate');
$config->ai->dataSource['project']['programplan'] = array('name', 'desc', 'status', 'begin', 'end', 'realBegan', 'realEnd', 'planDuration', 'progress', 'estimate', 'consumed', 'left');
$config->ai->dataSource['project']['execution']  = array('name', 'desc', 'status', 'begin', 'end', 'realBegan', 'realEnd', 'estimate', 'consumed', 'progress');

$config->ai->dataSource['story']['story']         = array('title', 'spec', 'verify', 'product', 'module', 'pri', 'category', 'estimate');
$config->ai->dataSource['execution']['execution'] = array('name', 'desc', 'estimate');
$config->ai->dataSource['execution']['tasks']     = array('name', 'pri', 'status', 'estimate', 'consumed', 'left', 'progress', 'estStarted', 'realStarted', 'finishedDate', 'closedReason');

$config->ai->dataSource['task']['task'] = array('name', 'pri', 'status', 'estimate', 'consumed', 'left', 'progress', 'estStarted', 'realStarted');

$config->ai->dataSource['case']['case']  = array('title', 'precondition', 'scene', 'product', 'module', 'pri', 'type', 'lastRunResult', 'status');
$config->ai->dataSource['case']['steps'] = array('desc', 'expect');

$config->ai->dataSource['bug']['bug'] = array('title', 'steps', 'severity','pri', 'status', 'confirmed', 'type');

$config->ai->dataSource['doc']['doc'] = array('title', 'addedBy', 'addedDate', 'editedBy', 'editedDate', 'content');

/* Available target form definations. Please also update `$lang->ai->targetForm` upon changes! */
$config->ai->targetForm = array();
$config->ai->targetForm['product']['tree/managechild']   = (object)array('m' => 'tree', 'f' => 'browse');
$config->ai->targetForm['product']['doc/create']         = (object)array('m' => 'doc', 'f' => 'create');
$config->ai->targetForm['story']['create']               = (object)array('m' => 'story', 'f' => 'create');
$config->ai->targetForm['story']['batchcreate']          = (object)array('m' => 'story', 'f' => 'batchCreate');
$config->ai->targetForm['story']['change']               = (object)array('m' => 'story', 'f' => 'change');
$config->ai->targetForm['story']['totask']               = (object)array('m' => 'task', 'f' => 'batchCreate');
$config->ai->targetForm['story']['testcasecreate']       = (object)array('m' => 'testcase', 'f' => 'create');
$config->ai->targetForm['story']['subdivide']            = (object)array('m' => 'story', 'f' => 'batchCreate');
$config->ai->targetForm['productplan']['edit']           = (object)array('m' => 'productplan', 'f' => 'edit');
$config->ai->targetForm['productplan']['create']         = (object)array('m' => 'productplan', 'f' => 'create');
$config->ai->targetForm['projectrelease']['doc/create']  = (object)array('m' => 'doc', 'f' => 'create');
$config->ai->targetForm['project']['risk/create']        = (object)array('m' => 'risk', 'f' => 'create');
$config->ai->targetForm['project']['issue/create']       = (object)array('m' => 'issue', 'f' => 'create');
$config->ai->targetForm['project']['doc/create']         = (object)array('m' => 'doc', 'f' => 'create');
$config->ai->targetForm['project']['programplan/create'] = (object)array('m' => 'programplan', 'f' => 'create');
$config->ai->targetForm['execution']['batchcreatetask']  = (object)array('m' => 'execution', 'f' => 'batchCreateTask');
$config->ai->targetForm['execution']['createtestreport'] = (object)array('m' => 'execution', 'f' => 'createTestReport');
$config->ai->targetForm['execution']['createqa']         = (object)array('m' => 'execution', 'f' => 'createQA');
$config->ai->targetForm['execution']['createrisk']       = (object)array('m' => 'execution', 'f' => 'createRisk');
$config->ai->targetForm['execution']['createissue']      = (object)array('m' => 'execution', 'f' => 'createIssue');
$config->ai->targetForm['task']['edit']                  = (object)array('m' => 'task', 'f' => 'edit');
$config->ai->targetForm['task']['batchCreate']           = (object)array('m' => 'task', 'f' => 'batchCreate');
$config->ai->targetForm['testcase']['edit']              = (object)array('m' => 'testcase', 'f' => 'edit');
$config->ai->targetForm['testcase']['createscript']      = (object)array('m' => 'testcase', 'f' => 'createScript');
$config->ai->targetForm['bug']['edit']                   = (object)array('m' => 'bug', 'f' => 'edit');
$config->ai->targetForm['bug']['story/create']           = (object)array('m' => 'story', 'f' => 'create');
$config->ai->targetForm['bug']['testcase/create']        = (object)array('m' => 'testcase', 'f' => 'create');
$config->ai->targetForm['doc']['create']                 = (object)array('m' => 'doc', 'f' => 'create');
$config->ai->targetForm['doc']['edit']                   = (object)array('m' => 'doc', 'f' => 'edit');

/* Used to check if form injection is available, generated from `$config->ai->targetForm`. */
$config->ai->availableForms = array();
foreach($config->ai->targetForm as $forms)
{
    foreach($forms as $form)
    {
        if(!empty($config->ai->availableForms[$form->m]) && in_array($form->f, $config->ai->availableForms[$form->m])) continue;
        $config->ai->availableForms[$form->m][] = $form->f;
    }
}

/* Used to format form redirection links, useful if method requires additional arguments. */
$config->ai->targetFormVars = array();
$config->ai->targetFormVars['story']     = array('change' => 'storyID=%d');
$config->ai->targetFormVars['execution'] = array();

/* Menu printing configurations. */
$config->ai->menuPrint = new stdclass();
/**
 * Menu location definations, defines acceptable module-methods and on page menu locations, etc.
 * Some are identical except for module name, reuse them as much as possible.
 *
 * @param string $module           prompt module name (actual module could differ from prompt module name)
 * @param string $targetContainer  injection target container selector
 * @param string $class            class of menu or dropdown button
 * @param string $buttonClass      specified class of action menu buttons
 * @param string $dropdownClass    specified class of dropdown menu button
 * @param string $objectVarName    object variable name of view
 * @param string $stylesheet       stylesheet to be injected
 * @param string $injectMethod     injection jQuery method, `append` by default
 * @see ./view/promptmenu.html.php
 */
$config->ai->menuPrint->locations = array();
$config->ai->menuPrint->locations['story']['view'] = (object)array(
    'module'          => 'story',
    'targetContainer' => '#mainContent .cell:first-of-type .detail:first-of-type .detail-title',
    'class'           => 'pull-right',
    'stylesheet'      => '#mainContent .cell:first-of-type .detail:first-of-type .detail-title>button {margin-left: 10px;} #mainContent .cell:first-of-type .detail:first-of-type .detail-content {margin-top: 12px;}'
);
$config->ai->menuPrint->locations['task']['view']             = clone $config->ai->menuPrint->locations['story']['view'];
$config->ai->menuPrint->locations['task']['view']->module     = 'task';
$config->ai->menuPrint->locations['testcase']['view']         = clone $config->ai->menuPrint->locations['story']['view'];
$config->ai->menuPrint->locations['testcase']['view']->module = 'case';
$config->ai->menuPrint->locations['bug']['view']              = clone $config->ai->menuPrint->locations['story']['view'];
$config->ai->menuPrint->locations['bug']['view']->module      = 'bug';
$config->ai->menuPrint->locations['projectstory']['view']     = clone $config->ai->menuPrint->locations['story']['view'];
$config->ai->menuPrint->locations['execution']['storyView']   = $config->ai->menuPrint->locations['story']['view'];

$config->ai->menuPrint->locations['execution']['view'] = (object)array(
    'module'          => 'execution',
    'injectMethod'    => 'prepend',
    'targetContainer' => '#mainContent.main-row > .col-4.side-col .detail:first-child',
    'class'           => 'pull-right'
);
$config->ai->menuPrint->locations['product']['view']         = clone $config->ai->menuPrint->locations['execution']['view'];
$config->ai->menuPrint->locations['product']['view']->module = 'product';
$config->ai->menuPrint->locations['project']['view']         = clone $config->ai->menuPrint->locations['execution']['view'];
$config->ai->menuPrint->locations['project']['view']->module = 'project';

$config->ai->menuPrint->locations['productplan']['view'] = (object)array(
    'module'          => 'productplan',
    'targetContainer' => '#mainMenu>.btn-toolbar.pull-right',
    'objectVarName'   => 'plan'
);
$config->ai->menuPrint->locations['projectplan']['view']     = $config->ai->menuPrint->locations['productplan']['view'];
$config->ai->menuPrint->locations['project']['view']         = clone $config->ai->menuPrint->locations['productplan']['view'];
$config->ai->menuPrint->locations['project']['view']->module = 'project';
$config->ai->menuPrint->locations['release']['view']         = clone $config->ai->menuPrint->locations['productplan']['view'];
$config->ai->menuPrint->locations['release']['view']->module = 'release';
unset($config->ai->menuPrint->locations['release']['view']->objectVarName);
$config->ai->menuPrint->locations['projectrelease']['view']  = clone $config->ai->menuPrint->locations['productplan']['view'];
unset($config->ai->menuPrint->locations['projectrelease']['view']->objectVarName);

$config->ai->menuPrint->locations['doc']['view'] = (object)array(
    'module'          => 'doc',
    'injectMethod'    => 'prepend',
    'targetContainer' => '#mainMenu>.btn-toolbar.pull-right',
);
