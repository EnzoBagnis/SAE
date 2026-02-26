<?php

namespace Core\Model;

use Core\Config\DatabaseConnection;

/**
 * Abstract Repository Base Class
 * Provides generic CRUD operations and magic finder methods
 */
abstract class AbstractRepository
{
    protected \PDO $pdo;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance()->getConnection();
    }

    /**
     * Expose the PDO connection for raw queries in controllers.
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Magic method handler for dynamic findBy methods
     * Supports patterns:
     * - findBy{Field}($value)
     * - findBy{Field1}And{Field2}($value1, $value2)
     * - findBy{Field1}And{Field2}And{Field3}($value1, $value2, $value3)
     *
     * @param string $method Method name
     * @param array $args Method arguments
     * @return mixed Query result
     * @throws \BadMethodCallException If method pattern is invalid
     */
    public function __call(string $method, array $args)
    {
        // Handle findBy{Field}
        if (preg_match('/^findBy([A-Z][a-zA-Z0-9]*)$/', $method, $matches)) {
            $field = $this->camelToSnake($matches[1]);
            return $this->findByField($field, $args[0] ?? null);
        }

        // Handle findBy{Field1}And{Field2}And...
        if (preg_match('/^findBy([A-Z][a-zA-Z0-9]*(?:And[A-Z][a-zA-Z0-9]*)*)$/', $method, $matches)) {
            $fieldsString = $matches[1];
            $fieldParts = explode('And', $fieldsString);

            $fields = [];
            foreach ($fieldParts as $fieldPart) {
                $fields[] = $this->camelToSnake($fieldPart);
            }

            $criteria = [];
            foreach ($fields as $index => $field) {
                if (!isset($args[$index])) {
                    throw new \BadMethodCallException("Argument manquant pour le champ : {$field}");
                }
                $criteria[$field] = $args[$index];
            }

            return $this->findByFields($criteria);
        }

        throw new \BadMethodCallException("Méthode {$method} non trouvée dans " . static::class);
    }

    /**
     * Find single record by field
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @return mixed Entity object or null
     */
    protected function findByField(string $field, mixed $value): mixed
    {
        $table = $this->getTableName();
        $sql = "SELECT * FROM {$table} WHERE {$field} = :value LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['value' => $value]);

        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Find single record by multiple fields
     *
     * @param array $criteria Associative array of field => value
     * @return mixed Entity object or null
     */
    protected function findByFields(array $criteria): mixed
    {
        $table = $this->getTableName();
        $conditions = [];
        $params = [];

        foreach ($criteria as $field => $value) {
            $conditions[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $conditions) . " LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Find record by ID
     *
     * @param int $id Record ID
     * @return mixed Entity object or null
     */
    public function findById(int $id): mixed
    {
        return $this->findByField('id', $id);
    }

    /**
     * Find all records
     *
     * @param int|null $limit Limit number of results
     * @param int $offset Offset for pagination
     * @return array Array of entity objects
     */
    public function findAll(?int $limit = null, int $offset = 0): array
    {
        $table = $this->getTableName();
        $sql = "SELECT * FROM {$table}";

        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $this->pdo->query($sql);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn($data) => $this->hydrate($data), $results);
    }

    /**
     * Count all records
     *
     * @return int Total count
     */
    public function count(): int
    {
        $table = $this->getTableName();
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$table}");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Delete record by primary key.
     * Override in child classes when the PK is not an integer named 'id'.
     *
     * @param mixed $id Record primary key value
     * @return bool True if deleted
     */
    public function delete($id): bool
    {
        $table = $this->getTableName();
        $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Convert camelCase to snake_case
     *
     * @param string $input CamelCase string
     * @return string snake_case string
     */
    protected function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $input));
    }

    /**
     * Convert snake_case to camelCase
     *
     * @param string $input snake_case string
     * @return string camelCase string
     */
    protected function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }

    /**
     * Hydrate entity from database row
     * Override this method in child classes for custom hydration
     *
     * @param array $data Database row data
     * @return mixed Entity object
     */
    protected function hydrate(array $data): mixed
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass();

        foreach ($data as $column => $value) {
            $property = $this->snakeToCamel($column);
            $setter = 'set' . ucfirst($property);

            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }

        return $entity;
    }

    /**
     * Extract data from entity for database operations
     *
     * @param mixed $entity Entity object
     * @return array Associative array of database columns and values
     */
    protected function extract(mixed $entity): array
    {
        $data = [];
        $reflection = new \ReflectionClass($entity);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            $value = $property->getValue($entity);

            if ($propertyName === 'id' && $value === null) {
                continue;
            }

            $columnName = $this->camelToSnake($propertyName);
            $data[$columnName] = $value;
        }

        return $data;
    }

    /**
     * Get table name for repository
     * Must be implemented by child classes
     *
     * @return string Table name
     */
    abstract protected function getTableName(): string;

    /**
     * Get entity class name for repository
     * Must be implemented by child classes
     *
     * @return string Fully qualified entity class name
     */
    abstract protected function getEntityClass(): string;
}

