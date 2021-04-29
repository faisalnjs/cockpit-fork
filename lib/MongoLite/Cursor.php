<?php

namespace MongoLite;

/**
 * Cursor object.
 */
class Cursor implements \Iterator {

    /**
     * @var boolean|integer
     */
    protected bool|int $position = false;

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var Collection object
     */
    protected Collection $collection;

    /**
     * @var string|null
     */
    protected ?string $criteria;

    /**
     * @var array|null
     */
    protected ?array $projection;

    /**
     * @var null|integer
     */
    protected ?int $limit = null;

    /**
     * @var null|integer
     */
    protected ?int $skip = null;

    /**
     * @var null|array
     */
    protected ?array $sort = null;

    /**
     * Constructor
     *
     * @param object $collection
     * @param mixed $criteria
     */
    public function __construct(Collection $collection, mixed $criteria, ?array $projection = null) {
        $this->collection  = $collection;
        $this->criteria    = $criteria;
        $this->projection  = $projection;
    }

    /**
     * Documents count
     *
     * @return integer
     */
    public function count(): int {

        if (!$this->criteria) {

            $stmt = $this->collection->database->connection->query('SELECT COUNT(*) AS C FROM '.$this->collection->database->connection->quote($this->collection->name));

        } else {

            $sql = ['SELECT COUNT(*) AS C FROM '.$this->collection->database->connection->quote($this->collection->name)];

            $sql[] = 'WHERE document_criteria("'.$this->criteria.'", document)';

            if ($this->limit) {
                $sql[] = 'LIMIT '.$this->limit;
            }

            $stmt = $this->collection->database->connection->query(\implode(' ', $sql));
        }

        $res  = $stmt->fetch(\PDO::FETCH_ASSOC);

        return intval(isset($res['C']) ? $res['C']:0);
    }

    /**
     * Set limit
     *
     * @param  int $limit
     * @return object       Cursor
     */
    public function limit(?int $limit): self {

        $this->limit = $limit;

        return $this;
    }

    /**
     * Set sort
     *
     * @param  mixed $sorts
     * @return object       Cursor
     */
    public function sort(?array $sorts): self {

        $this->sort = $sorts;

        return $this;
    }

    /**
     * Set skip
     *
     * @param  int $skip
     * @return object       Cursor
     */
    public function skip(?int $skip): self {

        $this->skip = $skip;

        return $this;
    }

    /**
     * Loop through result set
     *
     * @param  mixed $callable
     * @return object
     */
    public function each(mixed $callable): self {

        foreach ($this->rewind() as $document) {
            $callable($document);
        }

        return $this;
    }

    /**
     * Get documents matching criteria
     *
     * @return array
     */
    public function toArray(): array {
        return $this->getData();
    }


    /**
     * Get documents matching criteria
     *
     * @return array
     */
    protected function getData(): array {

        $conn = $this->collection->database->connection;
        $sql = ['SELECT document FROM '.$conn->quote($this->collection->name)];

        if ($this->criteria) {

            $sql[] = 'WHERE document_criteria("'.$this->criteria.'", document)';
        }

        if ($this->sort) {

            $orders = [];

            foreach ($this->sort as $field => $direction) {
                $orders[] = 'document_key('.$conn->quote($field).', document) '.($direction==-1 ? 'DESC':'ASC');
            }

            $sql[] = 'ORDER BY '.\implode(',', $orders);
        }

        if ($this->limit) {
            $sql[] = 'LIMIT '.$this->limit;

            if ($this->skip) { $sql[] = 'OFFSET '.$this->skip; }
        }

        $sql = implode(' ', $sql);

        $stmt      = $conn->query($sql);
        $result    = $stmt->fetchAll( \PDO::FETCH_ASSOC);
        $documents = [];

        if (!$this->projection) {

            foreach ($result as &$doc) {
                $documents[] = \json_decode($doc['document'], true);
            }

        } else {

            $exclude = [];
            $include = [];

            foreach ($this->projection as $key => $value) {

                if ($value) {
                    $include[$key] = 1;
                } else {
                    $exclude[$key] = 1;
                }
            }

            foreach ($result as &$doc) {

                $item = \json_decode($doc['document'], true);
                $id   = $item['_id'];

                if ($exclude) {
                    $item = \array_diff_key($item, $exclude);
                }

                if ($include) {
                    $item = array_key_intersect($item, $include);
                }

                if (!isset($exclude['_id'])) {
                    $item['_id'] = $id;
                }

                $documents[] = $item;
            }
        }

        return $documents;
    }

    /**
     * Iterator implementation
     */
    public function rewind() {

        if ($this->position!==false) {
            $this->position = 0;
        }
    }

    public function current(): array {

        return $this->data[$this->position];
    }

    public function key(): int {
        return $this->position;
    }

    public function next(): void {
        ++$this->position;
    }

    public function valid(): bool {

        if ($this->position===false) {

            $this->data     = $this->getData();
            $this->position = 0;
        }

        return isset($this->data[$this->position]);
    }

}

function array_key_intersect(&$a, &$b): array {

    $array = [];

    foreach ($a as $key => $value) {
        if (isset($b[$key])) $array[$key] = $value;
    }

    return $array;
}
