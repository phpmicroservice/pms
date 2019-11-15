# readme 读我懂一切
> 这是一个采用swoole做底层通讯,采用phalcon的类库做框架类库的框架,专门用于微服务编程

composer的packagist地址:
 https://packagist.org/packages/phpmicroservice/pms-frame


> 依赖版本
* php 7.2.*
* swoole 4.*
* phalcon  >3.4.*

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;这个分支用于 **dev-master** 的版本迭代,这是先进(坑死人不偿命)的版本,对于各个子版本的迭代请参考子分支,具体的稳定版本请参考 **releases** 发布和**composer**的版本发布,不到1.0.0以上版本请不要使用与运营环境,这条路比较坑,会颠得你胃疼的!

|事件|描述|
|-|-|
|server:beforeStart|在启动之前|
|server:onStart|启动时|
|server:onWorkerStart|进程启动时|
|server:readyJudge|准备判断|
|server:readySucceed|准备完成|
|server:onShutdown|关闭时|
|server:onPipeMessage||
|server:onManagerStart||
|server:onManagerStop||
|task:onTask||
|task:onPipeMessage||
|task:onWorkerStart|任务进程启动|
|work:onFinish||
|work:onPipeMessage||
|work:onWorkerStart|工作进程启动|
|work:onWorkerStop||
|work:onWorkerExit||

|app:init|应用初始化,启动时执行一次|

# 路由 
一个`/` 为预定义路由请勿占用,例如:`/connect`是产生链接的路由