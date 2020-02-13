<?php

namespace pms\Task;


class TxTask
{
    protected $dependency_data = [];
    protected $swoole_server;
    protected $trueData;
    protected $data;
    protected $task_id;
    protected $src_worker_id;


    public function __construct($swoole_server, $data)
    {
        $this->swoole_server = $swoole_server;
        $this->trueData = $data;
        $this->data = $data['data'] ? $data['data'] : $data[1];
    }

    /**
     * 设置任务进程id
     * @param $task_id
     */
    public function setTaskId($task_id)
    {
        $this->task_id = $task_id;
    }

    /**
     * 设置任务进程id
     * @param $task_id
     */
    public function setWorkId($src_worker_id)
    {
        $this->src_worker_id = $src_worker_id;
    }

    /**
     * 执行方法
     * @return mixed
     */
    final public function execute()
    {
        \Phalcon\Di::getDefault()->get('db')->connect();
        $startTime = microtime(true);
        $re = $this->run();
        $endTime = microtime(true);
        $data = $this->trueData;
        if ($re !== true) {
            $tmdata = [
                'xid' => $this->getXid(),
                'server' => SERVICE_NAME
            ];
            $proxyCS = $this->getProxyCS();
            $re56 = $proxyCS->request_return('tm', '/service/get_mes', $tmdata);
            if (!$re56) {

            } else {
                $mes = '';
                foreach ($re56['d'] as $value) {
                    $mes .= $value['type'] . '-' .
                        $value['message'] .
                        '-' . $value['code'] .
                        '-' . $value['server'] . ' & ';
                }
            }
            $data['message'] = trim($mes, ' & ');
        }

        $data['re'] = $re;
        $data['task_id'] = $this->task_id;
        $data['time'] = $endTime - $startTime;
        \Phalcon\Di::getDefault()->get('db')->close();
        return $data;
    }

    public function run()
    {
        $logger = $this->getLogger();
        $logger->info('task-AdemoTx-start: ' . var_export($this->trueData, true));
        $data = $this->trueData;
        if (isset($data['xid'])) {
            # 存在xid 就是由tm发起的
            $xid = $data['xid'];
        } else {
            $proxyCS = $this->getProxyCS();
            # 不存在xid 就是新发起的xa事务,需要请求tm进行事务创建
            $re = $proxyCS->request_return('tm',
                '/service/create', [
                    'server' => SERVICE_NAME,
                ]);

            if (!is_array($re) || $re['e'] || empty($re['d']['xid'])) {
                # 通知事务协调器 依赖完成的过程出错!
                # 出错的逻辑
                $logger->info('task-AdemoTx-create: 创建全局事务失败! ' . var_export($re, true));
                return '创建全局事务失败! ';
            }

            $this->trueData['xid'] = $re['d']['xid'];
            $xid = $re['d']['xid'];
        }


        $gtrid = $xid;
        $bqual = uniqid();
        $db = $this->getDb();
        $tmdata = [
            'xid' => $xid,
            'server' => SERVICE_NAME
        ];

        if (!$this->dependency()) {
            $logger->info('task-AdemoTx-add: 处理依赖失败');
            return "处理依赖失败!";
        }
        # 处理依赖完成
        $re = $this->getProxyCS()->request_return('tm', '/service/dependency', $tmdata);
        if (!is_array($re) || $re['e']) {
            # 通知事务协调器 依赖完成的过程出错!
            # 出错的逻辑
            $logger->info('task-AdemoTx-dependency: 处理依赖失败');
            return "附加业务处理失败!";
        }
        # 启动事务
        $re = $db->query("XA START " . "'$gtrid','$bqual'");

        $logicre = $this->logic();
        if ($logicre !== true) {

            $logger->info('task-AdemoTx-logic: 业务逻辑失败 - ' . var_export($this->trueData));
            $db->query('XA END ' . "'$gtrid','$bqual'");
            $tmdata['ems'] = [
                'm' => $logicre,
                't' => 'logic'
            ];
            # 保存失败,直接通知事务协调器,事务不能继续
            $re = $this->getProxyCS()->request_return('tm', '/service/rollback', $tmdata);
            if (!is_array($re) || !$re['e']) {
                # 通知事务协调器失败
            } else {
                # 通知事务协调器成功
            }
            # 不管咋地这个事务都得回滚
            # 事务自动回滚
            $db->query('XA ROLLBACK ' . "'$gtrid','$bqual'");
            return $logicre;
            #
        }
        $db->query('XA END ' . "'$gtrid','$bqual'");
        # 通知事务协调器事务 构建 完成,可以提交 2
        $re63 = $this->getProxyCS()->request_return('tm', '/service/end', $tmdata);
        if (!is_array($re63) || $re63['e']) {
            $logger->info('task-AdemoTx-end: 处理结束失败');
            # 没有成功的通知 事务协调器
            # 自动回滚
            $db->query('XA ROLLBACK ' . "'$gtrid','$bqual'");
            # 通知事务协调器 我要回滚了
            $tmdata['ems'] = [
                'm' => 'error',
                't' => 'end'
            ];
            $re72 = $this->getProxyCS()->request_return('tm', '/service/rollback', $tmdata);
            return "本地事务END出错!";

        } else {
            # 成功的通知了 事务协调器

        }
        # 继续往下走准备提交
        try {
            # 进行 准备提交
            $re101 = $db->query('XA PREPARE ' . "'$gtrid','$bqual'");
            $re = $this->getProxyCS()->request_return('tm', '/service/prepare', $tmdata);
            if (!is_array($re) || $re['e']) {
                # 要回滚的逻辑
                $logger->info('task-AdemoTx-PREPARE: 准备提交失败');
                $db->query('XA ROLLBACK ' . "'$gtrid','$bqual'");
                return "提交出错!";
            }
            # 进行提交
            $db->query('XA COMMIT ' . "'$gtrid','$bqual'");
            $re112 = $this->getProxyCS()->request_return('tm', '/service/commit', $tmdata);

        } catch (\PDOException $e) {
            $logger->info('task-AdemoTx-PDOException: 异常的失败.' . $e->getMessage());
            $db->query('XA ROLLBACK ' . "'$gtrid','$bqual'");
            $tmdata['ems'] = [
                'm' => $e->getMessage(),
                't' => 'commit'
            ];
            $re = $this->getProxyCS()->request_return('tm', '/service/rollback', $tmdata);
            return "数据库异常:" . $e->getMessage();
        }
        return true;
    }

    protected function getLogger(): \Phalcon\Logger\AdapterInterface
    {
        return \Phalcon\Di::getDefault()->get('logger');
    }

    protected function getProxyCS(): \pms\bear\ClientSync
    {
        return \Phalcon\Di::getDefault()->get('proxyCS');
    }

    protected function getDb(): \Phalcon\Db\Adapter\Pdo\Mysql
    {
        return \Phalcon\Di::getDefault()->get('db');
    }

    /**
     * 处理依赖
     */
    protected function dependency()
    {
        $this->b_dependenc();
        $tmdata = [
            'xid' => $this->trueData['xid'],
            'server' => SERVICE_NAME
        ];
        $tmdata['data'] = $this->dependency_data;
        $re = $this->getProxyCS()->request_return('tm', '/service/add', $tmdata);
        if (!is_array($re) || $re['e']) {
            return "附属业务失败!!";
        }
        return true;
    }

    protected function b_dependenc()
    {

    }

    protected function logic()
    {
        return false;
    }

    protected function getXid()
    {
        return $this->trueData['xid'];
    }

    final  public function finish()
    {
        $startFinishTime = microtime(true);
        $re = $this->end();
        $endFinishTime = microtime(true);
        $data = $this->trueData;
        $data['re'] = $re;
        $data['task_id'] = $this->task_id;
        $data['time'] = $endFinishTime - $startFinishTime;
        return $data;
    }

    public function end()
    {

    }

    /**
     * 获取任务的数据,并非传给swoole的真是数据
     * @return mixed
     */
    protected function getData()
    {
        return $this->trueData['data']??$this->trueData[1];
    }

    /**
     * 获取任务的name
     * @return mixed
     */
    protected function getName()
    {
        return $this->trueData['name']??$this->trueData[0];
    }

    /**
     * 增加依赖
     * @param string $server
     * @param string $tx_name
     * @param array $tx_data
     */
    protected function add_dependenc(string $server, string $tx_name, array $tx_data)
    {
        $this->dependency_data[] = [
            'server' => $server,
            'tx_data' => $tx_data,
            'tx_name' => $tx_name
        ];
    }

}