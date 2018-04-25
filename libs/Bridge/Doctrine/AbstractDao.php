<?php
/**
 * Description of AbstractDao.php.
 *
 * @package Kinone\Bundle\Bridge\Doctrine
 */

namespace Kinone\Bundle\Bridge\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use PDO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractDao
{
    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return string
     */
    abstract protected function getTableName();

    public function __construct(Connection $conn, LoggerInterface $logger = null)
    {
        $this->conn = $conn;
        if ($logger) {
            $this->logger = $logger;
        } else {
            $this->logger = new NullLogger();
        }
    }

    /**
     * @return QueryBuilder
     */
    protected function createQueryBuilder()
    {
        return $this->conn->createQueryBuilder();
    }

    protected function first(QueryBuilder $query)
    {
        return $query->execute()->fetch(PDO::FETCH_ASSOC);
    }

    protected function all(QueryBuilder $query)
    {
        return $query->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data)
    {
        $ret = $this->conn->insert($this->getTableName(), $data);
        return $ret ? $this->conn->lastInsertId() : false;
    }

    /**
     * @param array $datas
     * @return array
     * @throws \Exception
     */
    public function insertMulti(array $datas)
    {
        $ret = [];
        $this->conn->transactional(function() use ($datas, &$ret) {
            foreach($datas as $data) {
                $r = $this->conn->insert($this->getTableName(), $data);
                if (!$r) {
                    throw new \Exception('insert error', 1);
                }
                $ret[] = $this->conn->lastInsertId();
            }
        });
        return $ret;
    }

    public function update(array $data, array $identifier)
    {
        return $this->conn->update($this->getTableName(), $data, $identifier);
    }

    public function updateById($id, array $data)
    {
        return $this->conn->update($this->getTableName(), $data, compact('id'));
    }

    /**
     * @param array $ids
     * @param array $data
     * @return bool|int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateByIds(array $ids, array $data)
    {
        if (empty($ids)) {
            return false;
        }

        $ids = array_unique(array_map('intval', $ids));
        $set = [];
        foreach ($data as $columnName => $value) {
            $set[] = $columnName . ' = ?';
        }

        $params = array_values($data);

        $sql = 'UPDATE ' . $this->getTableName() . ' SET ' . implode(', ', $set)
            . ' WHERE id IN(' . implode(',', $ids) . ')';

        return $this->conn->executeUpdate($sql, $params);
    }

    public function updatePro(array $data, array $identifier)
    {
        $params = [];
        $query = $this->createQueryBuilder()
            ->update($this->getTableName());
        foreach($data as $key => $value) {
            $query->set('`' . $key . '`', '?');
            $params[] = $value;
        }

        if (!empty($identifier)) {
            list($where, $params2) = $this->handleIdentifier($identifier);
            $query->where($where);
            $params = array_merge($params, $params2);
        }

        return $query->setParameters($params)->execute();
    }

    /**
     * @param array $identifier
     * @return int
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function delete(array $identifier)
    {
        return $this->conn->delete($this->getTableName(), $identifier);
    }

    public function getById($id, $fields = '*', $fieldName = 'id')
    {
        list($where, $params) = $this->handleIdentifier([$fieldName => $id]);

        $query = $this->createQueryBuilder()
            ->select($this->implodeFieldName($fields))
            ->from($this->getTableName())
            ->where($where)
            ->setParameters($params);

        return $this->first($query);
    }

    public function getByIds(array $ids, $fields = '*', $fieldName = 'id')
    {
        $ids = array_filter(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        list($where, $params) = $this->handleIdentifier([$fieldName => $ids]);
        $query = $this->createQueryBuilder()
            ->select($this->implodeFieldName($fields))
            ->from($this->getTableName())
            ->where($where)
            ->setParameters($params);

        return $this->all($query);
    }

    public function getCount(array $identifier)
    {
        $query = $this->countQuery($identifier);
        return $this->first($query);
    }

    protected function _queryAll(array $identifier, array $order = [], $cols = '*', $cap = null)
    {
        $query = $this->createQueryBuilder()
            ->select($this->implodeFieldName($cols))
            ->from($this->getTableName());

        if ($cap) {
            $query->setMaxResults($cap);
        }

        foreach($order as $k => $v) {
            $query->addOrderBy('`' . $k . '`', $v);
        }

        if ($identifier) {
            list($where, $params) = $this->handleIdentifier($identifier);
            $query->where($where)->setParameters($params);
        }

        return $this->all($query);
    }

    final protected function _queryList(array $identifier, array $order = [], $start = 0, $num = 20, $cols = '*')
    {
        $res = $this->getCount($identifier);
        $total = isset($res['total']) ? $res['total'] : 0;
        $list = [];
        if ($total) {
            $query = $this->selectQuery($identifier, $order, $start, $num, $cols);
            $list = $this->all($query);
        }

        return compact('total', 'list');
    }

    protected function escapeLikeTarget($t)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $t);
    }

    /**
     * @param array $identifier
     * @param string $type
     * @return array
     */
    protected function handleIdentifier(array $identifier, $type = CompositeExpression::TYPE_AND)
    {
        $parts = [];
        $params = [];
        foreach($identifier as $k => $v) {
            if ($v instanceof CompositeExpression) {
                $parts[] = $v;
                continue;
            }

            $arr = explode(' ', $k);
            $name = array_shift($arr);
            $op = $arr ? array_shift($arr) : '=';

            if (is_array($v)) {
                $params = array_merge($params, $v);
                $v = '(' . implode(',', array_fill(0, count($v), '?')) . ')';
                $op = 'IN';
            } else {
                $params[] = $v;
                $v = '?';
            }

            $name = '`' . $name . '`';

            $parts[] = implode(' ', compact('name', 'op', 'v'));
        }

        $where = new CompositeExpression($type, $parts);
        return [$where, $params];
    }

    /**
     * @param array $identifier
     * @return QueryBuilder
     */
    protected function countQuery(array $identifier = [])
    {
        $query = $this->createQueryBuilder()
            ->select('COUNT(*) as total')
            ->from($this->getTableName());

        if ($identifier) {
            list($where, $params) = $this->handleIdentifier($identifier);
            $query->where($where)->setParameters($params);
        }

        return $query;
    }

    protected function selectQuery(array $identifier, array $order = [], $start = 0, $num = 20, $cols = '*')
    {
        $query = $this->createQueryBuilder()
            ->select($this->implodeFieldName($cols))
            ->from($this->getTableName())
            ->setFirstResult($start)
            ->setMaxResults($num);

        foreach($order as $k => $v) {
            $query->addOrderBy('`' . $k . '`', $v);
        }

        if ($identifier) {
            list($where, $params) = $this->handleIdentifier($identifier);
            $query->where($where)->setParameters($params);
        }

        return $query;
    }

    protected function implodeFieldName($names)
    {
        if (!is_array($names)) {
            return $names;
        }

        return '`' . implode('`,`', $names) . '`';
    }
}
