<?php

namespace pms;

use Phalcon\Db\Column as Column;
use Phalcon\Db\Index;

/**
 * 数据库的升级和创建
 * Class INU
 * @package Common
 */
class Dbcu extends \Phalcon\Di\Injectable
{
    /**
     * @var \Phalcon\Db\Adapter\Pdo\Mysql
     */
    private $connection;

    public function testUpdate()
    {
        $tables = include ROOT_DIR . "/db/common.php";
        if (empty($tables)) {
            return;
        }

        $this->connection = $this->getDataBaseConnection();

        foreach ($tables as $table) {
            $primaryKey = [];
            $tableName = $table['tableName'];
            if ($this->connection->tableExists($tableName)) {
                $schemaName = $table['schemaName'];
                #存在某个表 需要修改表信息
                $fields = $this->connection->describeColumns($tableName);
                list($dropFields, $addFields, $editFields) = [[], [], []];
                foreach ($fields as $field) {
                    $dropFields[] = $field->getName();
                }
                foreach ($table['field']['columns'] as $column) {
                    $save = false;
                    $fieldName = $column->getName();
                    foreach ($fields as $field) {
                        if ($fieldName == $field->getName()) {
                            $save = true;
                            if ($column->isPrimary()) {
                                $primaryKey[$fieldName] = [
                                    'file' => $fieldName,
                                    'autoIncrement' => $column->isAutoIncrement()
                                ];
                            }
                            $this->arrayRemoveElement($dropFields, $fieldName);
                            if ($this->compareFiled($field, $column)) {
                                break;
                            }
                            $editFields[] = $column;
                            break;
                        }
                    }

                    if ($save === false) {
                        $addFields[] = $column;
                    }
                }
                //处理外键
                $referenceIndexList = $this->dealReferences($tableName, $schemaName, $table['field']['references']);

                $this->deletedTableFields($tableName, $schemaName, $dropFields);
                $dealPrimary = $this->editTableFields($tableName, $schemaName, $editFields);
                $this->addTableFields($tableName, $schemaName, $addFields);

                // 处理索引
                $indexes = $table['field']['indexes'];
                $this->dealIndex($indexes, $referenceIndexList, $primaryKey, $tableName, $schemaName, $dealPrimary);

                continue;
            }

            #不存在某个表
            $this->createTable($tableName, $table);
        }
        var_dump('数据库处理结束');
    }

    /**
     * 比较 关联  外键
     * @param \Phalcon\Db\Reference $referenceNow
     * @param \Phalcon\Db\Reference $reference
     * @return bool
     */
    private function compareReference(\Phalcon\Db\Reference $referenceNow, \Phalcon\Db\Reference $reference): bool
    {
        return ($referenceNow->getColumns() == $reference->getColumns()
            && ($referenceNow->getOnDelete() == $reference->getOnDelete() || empty($referenceNow->getOnDelete()))
            && ($referenceNow->getOnUpdate() == $reference->getOnUpdate() || empty($referenceNow->getOnUpdate()))
            && $referenceNow->getReferencedColumns() == $reference->getReferencedColumns()
            && $referenceNow->getReferencedTable() == $reference->getReferencedTable());

    }

    /**
     * 比较字段
     * @param Column $field
     * @param Column $column
     * @return bool
     */
    private function compareFiled(\Phalcon\Db\Column $field, \Phalcon\Db\Column $column): bool
    {
        return ($field->getType() == $column->getType()
            && $field->getSchemaName() == $column->getSchemaName()
            && $field->isPrimary() == $column->isPrimary()
            && $field->getSize() == $column->getSize()
            && $field->isUnsigned() == $column->isUnsigned()
            && $field->isAutoIncrement() == $column->isAutoIncrement()
            && $field->hasDefault() == $column->hasDefault()
            && $field->getSchemaName() == $column->getSchemaName()
            && $field->getDefault() == $column->getDefault()
            && $field->isNotNull() == $column->isNotNull());
    }

    /**
     * 处理外键
     */
    private function dealReferences($tableName, $schemaName, $referencesNow)
    {
        list($dropList, $addList, $referenceIndexList) = array([], [], []);
        $connection = $this->getDataBaseConnection();
        // 获取'robots'表的所有外键
        $references = $connection->describeReferences($tableName);
        foreach ($references as $referenceName => $reference) {
            $dropList[$referenceName] = $reference;
        }
        if(is_array($referencesNow)){
            foreach ($referencesNow as $reference) {
                $save = false;
                $referenceName = $reference->getName();
                $referenceIndexList[$referenceName] = new Index($referenceName, $reference->getColumns(), 'Normal');
                // 打印引用的列
                if (array_key_exists($referenceName, $dropList)) {
                    $save = true;
                    if ($this->compareReference($reference, $dropList[$referenceName])) {
                        unset($dropList[$referenceName]);
                        continue;
                    }
                }
                $addList[] = $reference;
            }
        }

        foreach ($dropList as $dropName => $drop) {
            $connection->dropForeignKey($tableName, $schemaName, $dropName);
            $connection->dropIndex($tableName, $schemaName, $dropName);
        }
        foreach ($addList as $addReference) {
            $this->addReference($tableName, $addReference);
        }
        return $referenceIndexList;
    }

    /**
     * 处理索引
     *
     */
    private function dealIndex(array $fieldIndex, $referenceIndexList, $primaryKey, $tableName, $schemaName, $dealPrimary)
    {
        list($dropIndex, $addIndex) = array([], []);

        $this->connection = $this->getDataBaseConnection();
        // 获取表的所有索引
        $indexes = $this->connection->describeIndexes($tableName);
        var_dump($indexes,$tableName);
        foreach ($indexes as $index) {
            if (strtoupper($index->getType()) == 'PRIMARY') {
                if ($dealPrimary) {
                    continue;
                }
                if (!empty($primaryKey) && array_keys($primaryKey) == $index->getColumns()) {
                    continue;
                }
                $dropIndex['__PRIMARY__'] = $index;
                continue;
            }
            $indexName = $index->getName();

            if (array_key_exists($indexName, $referenceIndexList)) {
                if ($index->getColumns() == $referenceIndexList[$indexName]->getColumns()) {
                    continue;
                }
            }
            $dropIndex[$indexName] = $index;
        }
        foreach ($fieldIndex as $index) {
            $indexName = $index->getName();
            if (strtoupper($index->getType()) == 'PRIMARY' || empty($index->getType())) {
                if ($dealPrimary) {
                    continue;
                }

                $columnsArr = empty($primaryKey) ? $index->getColumns() : array_keys($primaryKey);
                if ($columnsArr == $dropIndex['__PRIMARY__']->getColumns()) {
                    unset($dropIndex['__PRIMARY__']);
                    continue;
                }
            }

            if (array_key_exists($indexName, $dropIndex)) {
                //不一样就删掉 且新增
                if ($index->getType() == $dropIndex[$indexName]->getType()
                    && $index->getColumns() == $dropIndex[$indexName]->getColumns()) {
                    unset($dropIndex[$indexName]);
                    continue;
                }
            }

            $addIndex[] = $index;
        }

        var_dump($dropIndex);
        # 删除索引
        foreach ($dropIndex as $key => $value) {
            $this->connection->dropIndex($tableName, $schemaName, $key);
        }
        # 增加索引
        foreach ($addIndex as $index) {
            $this->connection->addIndex($tableName, $schemaName, $index);
        }
    }

    /**
     *
     *添加外键
     */
    private function addReference($tableName, \Phalcon\Db\Reference $reference, $delete = 'RESTRICT', $update = 'RESTRICT')
    {
        $referenceName = $reference->getName();
        $foreignKey = implode(',', $reference->getColumns());
        $referencedTable = $reference->getReferencedTable();
        $referenceColumns = implode(',', $reference->getReferencedColumns());
        $sql = "ALTER TABLE $tableName ADD CONSTRAINT $referenceName FOREIGN KEY ($foreignKey) REFERENCES $referencedTable ($referenceColumns) ON DELETE $delete ON UPDATE $update";
        $this->connection->query($sql);
    }

    //修改字段
    private function editTableFields($tableName, $schemaName, $columnArray)
    {
        $dealPrimary = false;
        if (empty($columnArray)) {
            return $dealPrimary;
        }

        foreach ($columnArray as $column) {
            /**
             * @var $column \Phalcon\Db\Column
             */
            #设置自增时,主键必须搞定
            if ($column->isAutoIncrement() || $column->isPrimary()) {
                if (!$column->isPrimary()) {
                    var_dump($tableName . '修改自增字段失败, 因为他不是主键');
                    die;
                }

                //修改主键为当前键 判断主键是否一致
                $indexes = $this->connection->describeIndexes($tableName, $schemaName);
                $savePrimary = false;
                foreach ($indexes as $index) {
                    if (strtoupper($index->getType()) == 'PRIMARY') {
                        $savePrimary = true;
                        if ($index->getColumns() != [$column->getName()]) {
                            $this->connection->dropPrimaryKey($tableName, $schemaName);
                            $index = new Index($column->getName(), [$column->getName(), 'PRIMARY']);
                            $this->connection->addPrimaryKey($tableName, $schemaName, $index);
                        }
                        break;
                    }
                }
                if (!$savePrimary) {
                    $this->connection->addPrimaryKey($tableName, $schemaName,
                        new Index($column->getName(), [$column->getName()])
                    );
                }
                $dealPrimary = true;
            }

            $this->connection->modifyColumn($tableName, $schemaName, $column);
        }
        return $dealPrimary;
    }

    //删除字段
    private function deletedTableFields($tableName, $schemaName, $fieldArray)
    {
        if (empty($fieldArray)) {
            return;
        }

        foreach ($fieldArray as $field) {
            $this->connection->dropColumn(
                $tableName,
                $schemaName,
                $field
            );
        }
    }

    //添加字段
    private function addTableFields($tableName, $schemaName, $columnArray)
    {
        if (empty($columnArray)) {
            return;
        }

        foreach ($columnArray as $column) {
            $this->connection->addColumn($tableName, $schemaName, $column);
        }
    }

    /**
     * 获取数据库连接
     * @return \Phalcon\Db\Adapter\Pdo\Mysql
     */
    private function getDataBaseConnection()
    {
        return $this->db;
    }

    /**
     * 创建表
     * @param $tableName
     * @param array $table
     */
    private function createTable($tableName, array $table)
    {
        echo '<br />將要創建表' . $tableName;
        $s = $this->connection->createTable($tableName, $table['schemaName'], $table['field']);
        var_dump($s);
    }

    /**
     * 数组移除
     * @param $arr
     * @param $element
     */
    private function arrayRemoveElement(&$arr, $element)
    {
        if (in_array($element, $arr)) {
            array_splice($arr, array_search($element, $arr), 1);
        }
    }
}