<?php
/**
 * Job context
 * User: moyo
 * Date: 25/09/2017
 * Time: 5:15 PM
 */

namespace Carno\Coroutine;

use ArrayObject;

class Context extends ArrayObject
{
    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return $this->offsetExists($key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->offsetExists($key) ? $this->offsetGet($key) : null;
    }

    /**
     * @param string $key
     * @param mixed $val
     * @return static
     */
    public function set(string $key, $val) : self
    {
        $this->offsetSet($key, $val);
        return $this;
    }
}
