<?php declare(strict_types=1);
/**
 * This file is part of Sword.
 * @link     http://sword.kyour.cn
 * @document http://sword.kyour.cn/doc
 * @contact  kyour@vip.qq.com
 * @license  http://github.com/php-sword/sword/blob/master/LICENSE
 */
namespace Sword;

use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;

/**
 * Class Db
 * 基于查询构造器的封装，以支持类似ThinkPHP的查询构造器
 * @package Sword
 */
class Db extends QueryBuilder
{

    private $tableName;

    public static function new(string $table = ''): Db
    {
        return (new static())->table($table);
    }

    public function table(string $table): Db
    {
        $this->tableName = $table;
        return $this;
    }
    
    //使where支持数组
    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND'): Db
    {

        if(is_array($whereProp)){
            foreach ($whereProp as $v) {
                parent::where($v[0], $v[1], $v[2]?? '=', $v[3]?? 'AND');
            }
        }else{
            parent::where($whereProp, $whereValue, $operator, $cond);
        }
        return $this;
    }

    public function getSql($action = 'find') :string
    {

        $this->getOne($this->tableName);
        return $this->getLastPrepareQuery();
    }

    public function find(): ?array
    {
        $this->getOne($this->tableName);
        $data = DbManager::getInstance()->query($this, true, 'default');
        return $data->getResultOne();
    }

    public function findAll()
    {
        $this->get($this->tableName);
        $data = DbManager::getInstance()->query($this, true, 'default');
        return $data->getResult();
    }

    public function updateData(...$data)
    {
        parent::update($this->tableName, ...$data);
        $data = DbManager::getInstance()->query($this, true, 'default');
        return $data->getResult();
    }

    public function insertData(...$data)
    {
        parent::insert($this->tableName, ...$data);
        $data = DbManager::getInstance()->query($this, true, 'default');
        return $data->getLastInsertId();
    }

}
