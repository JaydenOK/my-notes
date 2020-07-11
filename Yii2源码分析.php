<?php


Yii2 分析
))))))))))
启动执行类的关系（从上到下）
index.php
(new yii\web\Application($config))->run();

class Application extends \yii\base\Application

abstract class Application extends Module  (\yii\base\Application 为抽象类)

class Module extends ServiceLocator

class ServiceLocator extends Component

class Component extends BaseObject

class BaseObject implements Configurable

interface Configurable
{
}

//内部流程相关类
class Yii extends \yii\BaseYii
class BaseYii
{
    public static $classMap = [];
	//此属性即Yii::$app 全局静态属性，如 yii\web\Application或yii\console\Application 对象
    public static $app;
    public static $aliases = ['@yii' => __DIR__];
    public static $container;
	
	public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }
}



)))))
入口文件 : \web\index.php
//加载配置
$config = require __DIR__ . '/../config/web.php';
//设置根目录别名，简短命名空间namespace app
Yii::setAlias('@app', dirname(__DIR__));
//配置到$config
(new yii\web\Application($config))->run();

)))))))))
Application继承\yii\base\Application
class Application extends \yii\base\Application
{
}

)))))))))
\yii\base\Application是一个抽象类，入口即使用此构造方法
abstract class Application extends Module
{
    public function __construct($config = [])
    {
		//注册当前入口对象yii\web\Application为Yii的静态属性$app
        Yii::$app = $this;
		//保存此Module单例到已加载的模块数组: Yii::$app->loadedModules[get_class($instance)] = $instance; (前面流程已知Application extends Module)
        static::setInstance($this);
		//标记应用执行状态
        $this->state = self::STATE_BEGIN;
		//对配置的一些检查,id,basePath,runtimePath,vendorPath,components等
        $this->preInit($config);
		//有配置components的错误处理类的话，做相关errorHandler注册
        $this->registerErrorHandler($config);
		//重点:通过Component自动配置,当然Component也是Application父类(不过此处无关继承)
        Component::__construct($config);
    }
}

)))))))))
类关系知道class Component extends BaseObject
BaseObject{
    public function __construct($config = [])
    {
        if (!empty($config)) {
			//执行自动配置
            Yii::configure($this, $config);
        }
		//此方法目的为继承此类的子类做一些初始化工作扩展
        $this->init();
    }
	
	public function init()
    {
    }
}

)))))))))))))
类关系可知: class Yii extends \yii\BaseYii (此类与BaseObject类似，有配置属性功能)
class BaseYii
{
    public static $classMap = [];
	//此属性即Yii::$app 全局静态属性，如 yii\web\Application或yii\console\Application 对象
    public static $app;
    public static $aliases = ['@yii' => __DIR__];
    public static $container;
	
	//最终配置逻辑在这，传什么对象及键值数组，就给什么对象做属性注册及复职
	public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }
}
