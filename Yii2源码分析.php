<?php


Yii2 基础源码分析
))))))))))
先大概看下Application($config)构造方法会执行的相关类列表（从上到下）
	
index . php
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

//内部流程会涉及到的类
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
入口文件 : \web\index . php
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


########################################################################   研究 (new yii\web\Application($config)) 构造操作 ################################################
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
	
	/**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->state = self::STATE_INIT;
        $this->bootstrap();
    }
}

)))))))))
类关系知道class Component extends BaseObject
BaseObject {
    public function __construct($config = [])
    {
        if (!empty($config)) {
            //执行自动配置
            Yii::configure($this, $config);
        }
        //此方法目的为继承此类的子类做一些初始化工作扩展，初始化时执行了\yii\base\Application init() 方法
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


########################################################################   接着研究(Application($config))->run()应用执行操作 #############
)))))))))))))))
实际执行类： yii\base\Application;
abstract class Application extends Module
{
	public function run()
    {
        try {
            $this->state = self::STATE_BEFORE_REQUEST;
			//加载事件函数,组件添加的事件
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
			
			//handleRequest程序执行周期主方法，为抽象方法， 拿到请求类,在子类具体实现类（如：yii\web\Application）得到响应类response
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
			
			//yii\web\Response响应数据;
            $response->send();

            $this->state = self::STATE_END;

            return $response->exitStatus;
        } catch (ExitException $e) {
            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;
        }
    }
}

))))))))))))))))
yii\web\Application 处理一个请求到响应的主方法 ：handleRequest

class Application extends \yii\base\Application
	public function handleRequest($request)
    {	
        if (empty($this->catchAll)) {
			//正常模式：处理正常访问请求
            try {
				//通过解析UrlManager路由及参数， Yii::$app->getUrlManager()->parseRequest($this);
                list($route, $params) = $request->resolve();
            } catch (UrlNormalizerRedirectException $e) {
                $url = $e->url;
                if (is_array($url)) {
                    if (isset($url[0])) {
                        // ensure the route is absolute
                        $url[0] = '/' . ltrim($url[0], '/');
                    }
                    $url += $request->getQueryParams();
                }

                return $this->getResponse()->redirect(Url::to($url, $e->scheme), $e->statusCode);
            }
        } else {
			//维护模式 ：catchAll，设置维护模式时的信息，第一个为路由，其它为传递的参数
            $route = $this->catchAll[0];
            $params = $this->catchAll;
            unset($params[0]);
        }
        try {
            Yii::debug("Route requested: '$route'", __METHOD__);
            $this->requestedRoute = $route;
			//解析完路由参数后，通过模块创建控制器,和方法并执行(\yii\base\module->runAction方法、controller->runAction())
            $result = $this->runAction($route, $params);
            if ($result instanceof Response) {
                return $result;
            }
			//得到Response对象
            $response = $this->getResponse();
            if ($result !== null) {
                $response->data = $result;
            }

            return $response;
        } catch (InvalidRouteException $e) {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'), $e->getCode(), $e);
        }
    }
}


)))))))))))))))))
yii\base\Controller类

class Controller extends Component implements ViewContextInterface
	public function createAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }
		//1，分离controller的单个action时，执行此逻辑，这里的 actions()默认返回空数组，你自己创建控制器时，可以覆盖此actions()方法，添加自己配置的方法
        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return Yii::createObject($actionMap[$id], [$id, $this]);
        }
		
		//基础模板，通过直接在controller, 编写类似 function actionAaaBbb(){} 方法时执行此逻辑
        if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $id)) {
            $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $id)));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }

        return null;
    }

	public function runAction($id, $params = [])
    {
        $action = $this->createAction($id);
        if ($action === null) {
            throw new InvalidRouteException('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
        }

        Yii::debug('Route to run: ' . $action->getUniqueId(), __METHOD__);

        if (Yii::$app->requestedAction === null) {
            Yii::$app->requestedAction = $action;
        }

        $oldAction = $this->action;
        $this->action = $action;

        $modules = [];
        $runAction = true;

        // call beforeAction on modules
        foreach ($this->getModules() as $module) {
            if ($module->beforeAction($action)) {
                array_unshift($modules, $module);
            } else {
                $runAction = false;
                break;
            }
        }

        $result = null;

        if ($runAction && $this->beforeAction($action)) {
            // 调用yii\base\Action类，带参数执行方法
            $result = $action->runWithParams($params);

            $result = $this->afterAction($action, $result);

            // call afterAction on modules
            foreach ($modules as $module) {
                /* @var $module Module */
                $result = $module->afterAction($action, $result);
            }
        }

        if ($oldAction !== null) {
            $this->action = $oldAction;
        }

        return $result;
    }
}


)))))))))
yii\base\Action类;
class Action extends Component
    public function runWithParams($params)
    {
		//查找run方法
        if (!method_exists($this, 'run')) {
            throw new InvalidConfigException(get_class($this) . ' must define a "run()" method.');
        }
		//执行yii\web\Application->bindActionParams方法,
		/*
			public function bindActionParams($action, $params)
			{				
				if ($action instanceof InlineAction) {
					$method = new \ReflectionMethod($this, $action->actionMethod);
				} else {
					$method = new \ReflectionMethod($action, 'run');
				}
			}
		*/
		//给方法绑定参数，通过反射类
        $args = $this->controller->bindActionParams($this, $params);
        Yii::debug('Running action: ' . get_class($this) . '::run(), invoked by '  . get_class($this->controller), __METHOD__);
        if (Yii::$app->requestedParams === null) {
            Yii::$app->requestedParams = $args;
        }
        if ($this->beforeRun()) {
			////真正执行我们自定义的方法,调用run方法
            $result = call_user_func_array([$this, 'run'], $args);
            $this->afterRun();
			//返回执行结果
            return $result;
        }

        return null;
    }
}
