<?php

namespace Vertex\Framework\Modeling;

class EntityCollection implements \Iterator {
    private $items = [];

    public function __construct(array $items = []) {
        $this->items = $items;
    }

    /**
     * 
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * 
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        return next($this->items);
    }

    /**
     * 
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     * 
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $key = $this->key();
        return ($key !== NULL && $key !== false);
    }

    /**
     * 
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
       reset($this->items);
    }

    public function add(Model $entity) {
        $this->items[$entity->id()] = $entity;
    }

    public function count() {
        return count($this->items);
    }

    public function only($id) {
        if (!array_key_exists($id, $this->items))
            return NULL;
        return $this->items[$id];
    }
}