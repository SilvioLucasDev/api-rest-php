<?php

namespace App\Infra\Repositories\SQLite;

use App\Infra\Exceptions\OperationException;
use App\Infra\Repositories\Interfaces\RecordRepositoryInterface;
use App\Infra\Repositories\SQLite\Helpers\Connection;
use App\Domain\Models\Record;
use DateTime;
use PDO;

class RecordRepository implements RecordRepositoryInterface
{
  private PDO    $db;
  private ?array $bindings;
  private ?array $clauses;
  private ?array $columns;
  private ?array $values;

  public function __construct()
  {
    $this->db = Connection::getInstance();
  }

  public function get(array $params): array
  {
    foreach ($params as $param => $value) {
      if (isset($value) && $value !== '') {
        if (($param !== 'order_by' && $param !== 'limit' && $param !== 'offset') && ($param === 'type' || $param === 'deleted')) {
          $this->bindings[":$param"] = $value;
          $this->clauses[] = "$param = :$param";
        }
      }
    }

    $sql = 'SELECT * FROM registros';
    if (!empty($this->clauses))
      $sql .= ' WHERE ' . implode(' AND ', $this->clauses);
    if (isset($params['order_by']) && $params['order_by'] !== '')
      $sql .= ' ORDER BY ' . $params['order_by'];
    if (isset($params['limit']) && is_numeric($params['limit'])) {
      $sql .= ' LIMIT ' . (int)$params['limit'];
      if (isset($params['offset']) && is_numeric($params['offset']))
        $sql .= ' OFFSET ' . (int)$params['offset'];
    }

    $stmt = $this->db->prepare($sql);
    if (isset($this->bindings)) {
      foreach ($this->bindings as $param => &$value) {
        $stmt->bindParam($param, $value);
      }
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findById(int $id): array|string
  {
    $sql = 'SELECT * FROM registros WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function save(Record $record): string
  {
    foreach ($record as $param => $value) {
      if (isset($value)) {
        if ($value instanceof DateTime) {
          $this->bindings[":$param"] = $value->format('Y-m-d H:i:s');
        } else {
          $this->bindings[":$param"] = $value;
        }
        $this->columns[] = $param;
        $this->values[] = ":$param";
      }
    }
    $sql = 'INSERT INTO registros (' . implode(', ', $this->columns) . ') VALUES (' . implode(', ', $this->values) . ')';
    $stmt = $this->db->prepare($sql);
    foreach ($this->bindings as $param => &$value) {
      $stmt->bindValue($param, $value);
    }
    if (!$stmt->execute()) throw new OperationException('Record creation!');
    return 'Record successfully created!';
  }

  public function delete(int $id): string
  {
    $sql = 'UPDATE registros SET deleted = 1 WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    $stmt->bindParam(':id', $id);
    if (!$stmt->execute()) throw new OperationException('Record deletion!');
    return 'Record successfully deleted!';
  }

  public function update(Record $record): string
  {
    foreach ($record as $param => $value) {
      if (isset($value)) {
        $this->bindings[":$param"] = $value;
        $this->clauses[] = "$param = :$param";
      }
    }
    $sql = 'UPDATE registros SET ';
    $sql .= implode(', ', $this->clauses) . ' WHERE id = :id';
    $stmt = $this->db->prepare($sql);
    foreach ($this->bindings as $param => &$value) {
      $stmt->bindValue($param, $value);
    }
    if (!$stmt->execute()) throw new OperationException('Record update!');
    return 'Record successfully updated!';
  }

  public function getLastInsertedId(): int
  {
    $sql = 'SELECT max(id) as id FROM registros';
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['id'];
  }
}
