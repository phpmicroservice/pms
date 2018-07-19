<?php

namespace pms\Task;


class TxTask extends Task
{
    protected $dependency_data = [];

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
                return false;
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
            return false;
        }
        # 处理依赖完成
        $re = $this->getProxyCS()->request_return('tm', '/service/dependency', $tmdata);
        if (!is_array($re) || $re['e']) {
            # 通知事务协调器 依赖完成的过程出错!
            # 出错的逻辑
            $logger->info('task-AdemoTx-dependency: 处理依赖失败');
            return false;
        }
        # 启动事务
        $re = $db->query("XA START " . "'$gtrid','$bqual'");


        if (!$this->logic()) {
            $logger->info('task-AdemoTx-logic: 业务逻辑失败 - ' . var_export($this->trueData));
            $db->query('XA END ' . "'$gtrid','$bqual'");
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
            return false;
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
            $re72 = $this->getProxyCS()->request_return('tm', '/service/rollback', $tmdata);
            return false;

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
                return false;
            }
            # 进行提交
            $db->query('XA COMMIT ' . "'$gtrid','$bqual'");
            $re112 = $this->getProxyCS()->request_return('tm', '/service/commit', $tmdata);

        } catch (\PDOException $e) {
            $logger->info('task-AdemoTx-PDOException: 异常的失败.' . $e->getMessage());
            $db->query('XA ROLLBACK ' . "'$gtrid','$bqual'");
            $re = $this->getProxyCS()->request_return('tm', '/service/rollback', $tmdata);
            return false;
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
            return false;
        }
        return true;
    }

    protected function b_dependenc()
    {

    }

    protected function logic(): bool
    {
        return false;
    }

    public function end()
    {

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