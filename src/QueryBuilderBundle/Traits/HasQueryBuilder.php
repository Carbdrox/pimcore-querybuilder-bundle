<?php

declare(strict_types=1);

namespace QueryBuilderBundle\Traits;

use QueryBuilderBundle\Services\QueryBuilder;
use Pimcore\Model\DataObject\Concrete;

trait HasQueryBuilder
{
    /**
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(self::class);
    }

    /**
     * @param string $field
     * @param string $order
     * @return QueryBuilder
     */
    public static function orderBy(string $field, string $order = 'asc'): QueryBuilder
    {
        return self::query()->orderBy($field, $order);
    }

    /**
     * @param int $limit
     * @return QueryBuilder
     */
    public static function limit(int $limit): QueryBuilder
    {
        return self::query()->limit($limit);
    }

    /**
     * @param int $offset
     * @return QueryBuilder
     */
    public static function offset(int $offset): QueryBuilder
    {
        return self::query()->offset($offset);
    }

    /**
     * @return int
     */
    public static function count(): int
    {
        return self::query()->count();
    }

    /**
     * @return Concrete|null
     */
    public static function first(): Concrete|null
    {
        return self::query()->first();
    }

    /**
     * @return array
     */
    public static function all(): array
    {
        return self::query()->get();
    }

    /**
     * @param string|bool|int|float|null $value
     */
    public static function where(string|\Closure $field, string $operation, mixed $value, bool $escaped = true): QueryBuilder
    {
        return self::query()->where($field, $operation, $value, $escaped);
    }
}
