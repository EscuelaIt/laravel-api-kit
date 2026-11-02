<?php

declare(strict_types=1);

namespace EscuelaIT\APIKit;

trait BuildsLikeQueries
{
    /**
     * Adds a "contains" search on one or several columns. It can't use a regular index.
     *
     * @param mixed $query
     *
     * @return mixed The same query, unconstrained if the value is empty or not scalar
     */
    protected function whereContains($query, array|string $columns, mixed $value)
    {
        return $this->applyLikeSearch($query, (array) $columns, $value, '%');
    }

    /**
     * Adds a "starts with" search on one or several columns. It can use a regular index.
     *
     * @param mixed $query
     *
     * @return mixed The same query, unconstrained if the value is empty or not scalar
     */
    protected function whereStartsWith($query, array|string $columns, mixed $value)
    {
        return $this->applyLikeSearch($query, (array) $columns, $value, '');
    }

    private function applyLikeSearch($query, array $columns, mixed $value, string $prefix)
    {
        // Filters convert the strings 'true' and 'false' into booleans
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        if ([] === $columns || !is_scalar($value) || '' === (string) $value) {
            return $query;
        }

        // Grouped so the OR conditions can't escape the constraints of the query
        return $query->where(function ($group) use ($columns, $value, $prefix): void {
            foreach ($columns as $column) {
                $this->orWhereLikePattern($group, $column, (string) $value, $prefix);
            }
        });
    }

    private function orWhereLikePattern($query, string $column, string $value, string $prefix): void
    {
        $driver = $query->getConnection()->getDriverName();
        $wildcards = 'sqlsrv' === $driver ? '%_\[' : '%_\\';
        $pattern = $prefix.addcslashes($value, $wildcards).'%';

        // Backslash is the default LIKE escape character only on these engines
        if (in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            $query->whereLike($column, $pattern, false, 'or');

            return;
        }

        $query->whereRaw($query->getGrammar()->wrap($column)." LIKE ? ESCAPE '\\'", [$pattern], 'or');
    }
}
