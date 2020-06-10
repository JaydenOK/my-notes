<?php

##############  Yii2高级模板: 模块化配置，多模块配置(Yii2基础模板类似配置即可,注意检查命名空间是否正确 app)   ##############  
下载、安装高级模板
composer create-project --prefer-dist yiisoft/yii2-app-advanced im-help
初始化./init或执行 init.bat ， 配置nginx 域名，支持rewrite隐藏index.php等等  安装完成!



开始配置多模块
高级模板有三个应用
backend
frontend
console

选择一个backend应用，演示创建多模块
在backend/下创建modules目录
然后在modules创建2个模块目录: index, im
分别在模块目录创建对应的文件:IndexModule.php ,  ImModule.php
以其中一个ImModule.php为例:
内容如下:继承yii\base\Module;
<?php

namespace backend\modules\im;

use yii\base\Module;

class ImModule extends Module
{
    public $id = 'ImModule';
}

然后在创建 backend/im/controllers目录
创建测试控制器 ChatController.php
内容如下:
<?php

namespace backend\modules\im\controllers;

use Yii;
use yii\web\Controller;

class ChatController extends Controller
{

    public function actionIndex()
    {
        return '欢迎';
    }

}

然后关键的模块关联配置
在backend/config/main.php 加入如下配置
    //定义默认访问路由
    'defaultRoute' => 'index/index/index',
    //多模块配置
    'modules' => require('modules.php'),
		//路由： enablePrettyUrl：隐藏入口文件index.php
	'urlManager' => [
		'enablePrettyUrl' => true,
		'showScriptName' => false,
		'rules' => [
		],
	],
	
backend/config/modules.php 配置如下，以后要新增模块，按此配置添加即可
<?php
/**
 * 定义模块
 */
return [
    'index' => [
        'class' => 'backend\modules\index\IndexModule',
    ],
    'im' => [
        'class' => 'backend\modules\im\ImModule',
    ],
];


配置完成！
访问查看结果: http://xxxxxx.cc/im/chat/index
