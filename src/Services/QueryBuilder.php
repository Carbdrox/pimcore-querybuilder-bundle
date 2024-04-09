<?php declare(strict_types=1);

namespace QueryBuilderBundle\Services;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\Paginator;
use Pimcore\Model\DataObject\Concrete as DataObjectConcrete;
use Pimcore\Model\DataObject\Listing\Concrete as ListingConcrete;

class QueryBuilder
{
    private string $class = '';
    private string $listingClass = '';

    private array $wheres = [];

    private array $joins = [];

    private array $group = [];

    private ?int $limit = null;

    private ?int $offset = null;

    private array $orders = [];

    public function __construct(string $class)
    {
        $this->class = $class;
        $this->listingClass = $class.'\\Listing';
        if (!class_exists($this->listingClass)) {
            $this->listingClass = get_parent_class($class).'\\Listing';
        }

        if (!class_exists($this->listingClass)) {
            throw new \RuntimeException('Missing class '.$this->listingClass);
        }
    }

    private function compileJoins(ListingConcrete $listing): ListingConcrete
    {
        if (count($this->joins) > 0) {
            $listing->onCreateQueryBuilder(
                function (DoctrineQueryBuilder $queryBuilder) use ($listing): void {
                    $baseTable = $listing->getDao()->getTableName();

                    $selects = array_map(fn (array $join) => $join['name'].'.*', $this->joins);

                    $queryBuilder->select(
                        sprintf('%s.* , %s', $baseTable, implode(' , ', $selects))
                    );

                    foreach ($this->joins as $join) {
                        extract($join);

                        $pivotName = 'object_relations_'.$name;

                        $queryBuilder->leftJoin(
                            $baseTable,
                            $pivotName,
                            $pivotName,
                            sprintf('%s.dest_id = %s.id', $pivotName, $baseTable)
                        );

                        $queryBuilder->innerJoin(
                            $pivotName,
                            $table,
                            $name,
                            sprintf('%s.src_id = %s.id', $pivotName, $name)
                        );
                    }
                }
            );
        }

        return $listing;
    }

    private function compileWhere(array $wheres = null): string
    {
        $wheres = is_null($wheres) ? $this->wheres : $wheres;
        $query = '';
        foreach ($wheres as $where) {
            if ('basic' === $where['type']) {
                $query .= sprintf(
                    ' %s %s %s %s',
                    $query ? $where['boolean'] : '',
                    str_contains($where['field'], '.') ? $where['field'] : '`'.$where['field'].'`',
                    $where['operation'],
                    $where['escaped'] ? $this->escape($where['value']) : $where['value']
                );
            } elseif ('nested' === $where['type']) {
                $query .= sprintf(
                    ' %s (%s)',
                    $query ? $where['boolean'] : '',
                    $this->compileWhere($where['query']->wheres)
                );
            }
        }

        return trim($query);
    }

    /**
     * @param string|null $operation
     *
     * @return $this
     */
    public function where(string|\Closure $field, string $operation = '=', mixed $value = '', bool $escaped = true): self
    {
        if ($field instanceof \Closure) {
            return $this->whereNested($field);
        }

        $this->wheres[] = [
            'type' => 'basic',
            'boolean' => 'and',
            'field' => $field,
            'operation' => $operation,
            'value' => $value,
            'escaped' => $escaped,
        ];

        return $this;
    }

    /**
     * @return $this
     */
    public function join(string $tableName, string $name): self
    {
        $this->joins[] = [
            'table' => $tableName,
            'name' => $name,
        ];

        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->group[] = $column;

        return $this;
    }

    /**
     * @return $this
     */
    public function orWhere(string|\Closure $field, string $operation, mixed $value, bool $escaped = true): self
    {
        if ($field instanceof \Closure) {
            return $this->whereNested($field);
        }

        $this->wheres[] = [
            'type' => 'basic',
            'boolean' => 'or',
            'field' => $field,
            'operation' => $operation,
            'value' => $value,
            'escaped' => $escaped,
        ];

        return $this;
    }

    /**
     * @return $this
     */
    public function whereNested(\Closure $callback): self
    {
        call_user_func($callback, $query = new self($this->class));
        if (count($query->wheres)) {
            $this->wheres[] = [
                'type' => 'nested',
                'boolean' => 'and',
                'query' => $query,
            ];
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function orWhereNested(\Closure $callback): self
    {
        call_user_func($callback, $query = new self($this->class));

        if (count($query->wheres)) {
            $this->wheres[] = [
                'type' => 'nested',
                'boolean' => 'or',
                'query' => $query,
            ];
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function orderBy(string $field, string $order = 'asc'): self
    {
        $order = mb_strtolower($order);
        $order = in_array($order, ['asc', 'desc']) ? $order : 'asc';
        $this->orders[] = ['field' => $field, 'order' => $order];

        return $this;
    }

    /**
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->limit = max(1, $limit);

        return $this;
    }

    /**
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->offset = max(0, $offset);

        return $this;
    }

    /**
     * @param string|bool|float|int|null $value
     */
    public function escape(mixed $value): string|float|int
    {
        if (is_array($value)) {
            return sprintf('(%s)', implode(',', array_map(fn ($value) => $this->escape($value), array_values($value))));
        } elseif (is_null($value)) {
            return 'NULL';
        } elseif (is_float($value) || is_int($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return true === $value ? 'TRUE' : 'FALSE';
        } elseif (is_object($value)) {
            if (method_exists($value, 'toString')) {
                return $this->escape($value->toString());
            }
            if (method_exists($value, '__toString')) {
                return $this->escape($value->__toString());
            }
            if (method_exists($value, 'toArray')) {
                return $this->escape($value->toArray());
            }
            if (method_exists($value, '__toArray')) {
                return $this->escape($value->__toArray());
            }

            return $this->escape(serialize($value));
        }

        $value = strtr(
            $value,
            [
                "\x00" => '\x00',
                "\n" => '\n',
                "\r" => '\r',
                '\\' => '\\\\',
                "'" => "\'",
                '"' => '\"',
                "\x1a" => '\x1a',
            ]
        );

        return '"'.$value.'"';
    }

    /**
     * @return DataObjectConcrete[]
     */
    public function get(): array
    {
        return $this->getListing()->load();
    }

    public function getListing(): ListingConcrete
    {
        $listing = new $this->listingClass();
        $listing = $this->compileJoins($listing);

        if ($where = $this->compileWhere()) {
            $listing->setCondition($where);
        }
        if ($this->limit) {
            $listing->setLimit($this->limit);
        }
        if ($this->offset) {
            $listing->setOffset($this->offset);
        }
        if (count($this->orders)) {
            $listing->setOrderKey(array_map(fn ($sort) => $sort['field'], $this->orders));
            $listing->setOrder(array_map(fn ($sort) => $sort['order'], $this->orders));
        }
        if (count($this->group)) {
            $listing->setGroupBy(implode(', ', $this->group));
        }

        return $listing;
    }

    public function count(): int
    {
        return $this->getListing()->getTotalCount();
    }

    public function first(): DataObjectConcrete|null
    {
        $list = $this->getListing()->setLimit(1)->setOffset(0)->load();

        return 1 === count($list) ? $list[0] : null;
    }

    public function toSql(): string
    {
        return $this->getListing()->getQueryBuilder()->getSQL();
    }

    public function paginate(int $page = 1, int $perPage = 10): PaginationInterface
    {
        /* @var Paginator $paginator */
        $paginator = \Pimcore::getKernel()->getContainer()->get('knp_paginator');

        return $paginator->paginate($this->getListing(), $page, $perPage);
    }
}
