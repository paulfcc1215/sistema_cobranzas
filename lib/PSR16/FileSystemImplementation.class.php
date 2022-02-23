<?php
namespace Psr\SimpleCache;

class FileSystemImplementation implements CacheInterface {
	private $db;
	private $table_id;
	private static $dbPrepared=false;
	
	function __construct($db) {
		$this->table_id=uniqid();
		$this->db=$db;
		$this->db->query('CREATE TEMPORARY TABLE psr_'.$this->table_id.' ("key" TEXT, "value" TEXT)');
		
		if(!self::$dbPrepared) {
			$this->db->prepare('psr16_put','INSERT INTO psr_'.$this->table_id.'("key","value") VALUES ($1,$2)');
			$this->db->prepare('psr16_get','SELECT * FROM psr_'.$this->table_id.' WHERE "key"=$1');
			$this->db->prepare('psr16_del','DELETE FROM psr_'.$this->table_id.' WHERE "key"=$1');
			self::$dbPrepared=true;
		}
	}
	
	function buildIndex() {
		$this->db->query('CREATE INDEX i'.uniqid().' ON psr_'.$this->table_id.' USING BTREE ("key")');
	}
    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get($key, $default = null) {
		
		$ret=$this->db->execute('psr16_get',array($key));
		if($ret->numRows()==0) return $default;
		$ret=$ret->current()['value'];
		$ret=unserialize(base64_decode($ret));
		return $ret;
	}

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                 $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store. Must be serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null) {
		$arr=array($key,base64_encode(serialize($value)));
		$this->db->execute('psr16_put',$arr);
		return true;
	}

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key) {
		$this->db->execute('psr16_del',array($key));
	}

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear() {
		$this->db->query('TRUNCATE psr_'.$this->table_id);
		return true;
	}

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null) {
		$ret=array();
		foreach($keys as $k) {
			$ret[$k]=$this->get($k);
		}
	}

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable               $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null) {
		foreach($values as $k=>$v) {
			$this->set($k,$v,$ttl);
		}
		return true;
	}

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys) {
		foreach($keys as $k) {
			$this->delete($k);
		}
		return true;
	}

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it, making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key) {
		$ret=$this->db->query('SELECT "key" FROM psr_'.$this->table_id.' WHERE "key"=\''.$this->db->escape($key).'\'');
		return ($ret->numRows()>0);
	}
}