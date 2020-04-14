<?php

/**
 * Copyright (c) 2011-2013, Carl Oscar Aaro
 * All rights reserved.
 *
 * New BSD License
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 *  * Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 *  * Neither the name of Carl Oscar Aaro nor the names of its
 *    contributors may be used to endorse or promote products derived from this
 *    software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 **/

/**
 * Redis cache backend for Zend Framework. Extends Zend_Cache_Backend
 * Supports tags and cleaning modes (except CLEANING_MODE_NOT_MATCHING_TAG)
 * Uses the PHP module phpredis by Nicolas Favre-Felix available at https://github.com/nicolasff/phpredis
 *
 * @category Zend
 * @author Carl Oscar Aaro <carloscar@agigen.se>
 */

/**
 * @see Zend_Cache_Backend_Interface
 */
require_once 'Zend/Cache/Backend/ExtendedInterface.php';

/**
 * @see Zend_Cache_Backend
 */
require_once 'Zend/Cache/Backend.php';

class Zend_Cache_Backend_Redis extends Zend_Cache_Backend implements Zend_Cache_Backend_ExtendedInterface
{
	/**
	 * Default Values
	 */
	const DEFAULT_HOST = '127.0.0.1';
	const DEFAULT_PORT =  6379;
	const DEFAULT_PERSISTENT = true;
	const DEFAULT_DBINDEX = 0;
	const COMPRESS_PREFIX = ":\x1f\x8b";

	protected $_options = array(
		'servers' => array(
			array(
				'host' => self::DEFAULT_HOST,
				'port' => self::DEFAULT_PORT,
				'persistent' => self::DEFAULT_PERSISTENT,
				'dbindex' => self::DEFAULT_DBINDEX,
			),
		),
		'key_prefix' => '',
	);

	/**
	 * @var int
	 */
	protected $_compressThreshold = 20480;

	/**
	 * @var string
	 */
	protected $_compressionLib;

	/**
	 * @var int
	 */
	protected $_compressData = 3;


	/**
	 * Redis object
	 *
	 * @var mixed redis object
	 */
	protected $_redis = null;

	/**
	 * Constructor
	 *
	 * @param array $options associative array of options
	 * @throws Zend_Cache_Exception
	 * @return void
	 */
	public function __construct(array $options = [])
	{
		if (!extension_loaded('redis')) {
			Zend_Cache::throwException('The redis extension must be loaded for using this backend !');
		}
		parent::__construct($options);
		$this->_redis = new Redis;

		foreach ($this->_options['servers'] as $server) {
			if (!array_key_exists('port', $server)) {
				$server['port'] = self::DEFAULT_PORT;
			}
			if (!array_key_exists('host', $server)) {
				$server['host'] = self::DEFAULT_HOST;
			}
			if (!array_key_exists('persistent', $server)) {
				$server['persistent'] = self::DEFAULT_PERSISTENT;
			}
			if (!array_key_exists('dbindex', $server)) {
				$server['dbindex'] = self::DEFAULT_DBINDEX;
			}
			if ($server['persistent']) {
				$result = $this->_redis->pconnect($server['host'], $server['port']);
			} else {
				$result = $this->_redis->connect($server['host'], $server['port']);
			}

			if ($result) {
				$this->_redis->select($server['dbindex']);
			} else {
				$this->_redis = null;
			}

			if (isset($options['compression_lib'])) {
				$this->_compressionLib = $options['compression_lib'];
			} else if (function_exists('snappy_compress')) {
				$this->_compressionLib = 'snappy';
			} else {
				$this->_compressionLib = 'gzip';
			}

			$this->_compressPrefix = substr($this->_compressionLib, 0, 2) . self::COMPRESS_PREFIX;
		}
	}

	/**
	 * Returns status on if cache backend is connected to Redis
	 *
	 * @return bool true if cache backend is connected to Redis server.
	 */
	public function isConnected()
	{
		if ($this->_redis)
			return true;
		return false;
	}


	/**
	 * Test if a cache is available for the given id and (if yes) return it (false else)
	 *
	 * @param string $id cache id
	 * @param boolean $doNotTestCacheValidity if set to true, the cache validity won't be tested
	 * @return string|false cached datas
	 */
	public function load($id, $doNotTestCacheValidity = false)
	{
		if (!($this->_test($id, $doNotTestCacheValidity))) {
			// The cache is not hit !
			return false;
		}
		$data = $this->_load($id);
		return $data;
	}

	/**
	 * Test if a cache is available or not (for the given id)
	 *
	 * @param string $id cache id
	 * @return mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
	 */
	public function test($id)
	{
		if (!$this->_redis) {
			return false;
		}
		$tmp = $this->_redis->exists($id);
		return $tmp;
	}

	/**
	 * Set the Redis connection in transaction mode. All coming Redis calls will be executed until transactionEnd() is called.
	 *
	 * @return bool true if transaction mode is enabled and the call is successful. false on error.
	 */
	public function transactionBegin()
	{
		return $this->_redis->multi();
	}

	/**
	 * Execute the Redis transaction started with transactionBegin(). Also completes the transaction and puts the Redis connection
	 * back into normal mode.
	 *
	 * @return array result set of all executed commands in the transaction.
	 */
	public function transactionEnd()
	{
		return $this->_redis->exec();
	}

	/**
	 * Save some string datas into a cache record
	 *
	 * Note : $data is always "string" (serialization is done by the
	 * core not by the backend)
	 *
	 * @param  string $data             Datas to cache
	 * @param  string $id               Cache id
	 * @param  mixed  $tags             Array of strings, the cache record will be tagged by each string entry, if false, key
	 *                                  can only be read if $doNotTestCacheValidity is true
	 * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
	 * @return boolean true if no problem
	 */
	public function save($data, $id, $tags =[], $specificLifetime = false)
	{
		if (!$this->_redis) {
			return false;
		}

		$compressedData = $this->_encodeData($data, $this->_compressData);

		$lifetime = $this->getLifetime($specificLifetime);
		if ($lifetime === null) {
			$return = $this->_redis->set($id, $compressedData);
		} else {
			$return = $this->_redis->setex($id, $lifetime, $compressedData);
		}
		if ($return === false) {
			$rsCode = $this->_redis->getLastError();
			$this->_log("RedisCluster::set() failed: [{$rsCode}]");
		}
		if (count($tags) > 0) {
			$this->_log(self::METHOD_UNSUPPORTED_BY_REDISCLUSTER_BACKEND);
		}

		return $return;
	}

	/**
	 * Save some string datas into a cache record. Only the specific key will be stored and no tags.
	 * Can only be read by load() if $doNotTestCacheValidity is true
	 *
	 * Note : $data is always "string" (serialization is done by the
	 * core not by the backend)
	 *
	 * @param  string $data             Datas to cache
	 * @param  string $id               Cache id
	 * @param  int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
	 * @return boolean true if no problem
	 */
	protected function _storeKey($data, $id, $specificLifetime = false)
	{
		if (!$this->_redis)
			return false;

		$lifetime = $this->getLifetime($specificLifetime);

		if ($lifetime === null) {
			return $this->_redis->set($this->_keyFromId($id), $data);
		} else {
			return $this->_redis->setex($this->_keyFromId($id), $lifetime, $data);
		}
	}

	/**
	 * Prefixes key ID
	 *
	 * @param string $id cache key
	 * @return string prefixed id
	 */
	protected function _keyFromId($id)
	{
		return $this->_options['key_prefix'] . $id;
	}

	/**
	 * Prefixes tag ID
	 *
	 * @param string $id tag key
	 * @return string prefixed tag
	 */
	protected function _keyFromTag($id)
	{
		return $this->_options['key_prefix'] . $id;
	}

	/**
	 * Prefixes item tag ID
	 *
	 * @param string $id item tag key
	 * @return string prefixed item tag
	 */
	protected function _keyFromItemTags($id)
	{
		return $this->_options['key_prefix'] . $id;
	}

	/**
	 * Remove a cache record
	 *
	 * @param  string $id cache id
	 * @return boolean true if no problem
	 */
	public function remove($id, $hardReset = false)
	{
		return (boolean)$this->_redis->delete($id);
	}


	/**
	 * Remove a cache tag record
	 *
	 * @param  string $tag cache tag
	 * @return boolean true if no problem
	 */
	public function removeTag($tag)
	{
		if (!$this->_redis)
			return false;

		if (!$tag)
			return false;
		if (is_string($tag))
			$id = array($tag);
		if (!count($tag))
			return false;
		$deleteTags = [];
		foreach ($tag as $t) {
			$deleteTags[] = $this->_keyFromTag($t);
		}
		if ($deleteTags && count($deleteTags))
			$this->_redis->delete($deleteTags);

		return true;
	}

	/**
	 * Returns wether a specific member key exists in the Redis set
	 *
	 * @param string $member
	 * @param string $set
	 * @return bool true or false
	 */
	public function existsInSet($member, $set)
	{
		if (!$this->_redis)
			return null;

		if (!$this->_redis->sIsMember($this->_keyFromId($set), $member))
			return false;
		return true;
	}

	/**
	 * Adds a key to a set
	 *
	 * @param mixed $member key(s) to add
	 * @param string $set
	 * @param string $specificLifetime lifetime, null for persistant
	 * @return bool result of the add
	 */
	public function addToSet($member, $set, $specificLifetime = false)
	{
		if (!$this->_redis)
			return null;

		$lifetime = $this->getLifetime($specificLifetime);

		if (is_array($member)) {
			$redis = $this->_redis;
			$return = call_user_func_array(array($redis, 'sAdd'), array_merge(array($this->_keyFromId($set)), $member));
		} else {
			$return = $this->_redis->sAdd($this->_keyFromId($set), $member);
		}
		if ($lifetime !== null)
			$this->_redis->setTimeout($this->_keyFromId($set), $lifetime);

		return $return;
	}

	/**
	 * Removes a key from a redis set.
	 *
	 * @param mixed $member key(s) to remove
	 * @param string $set
	 * @return bool result of removal
	 */
	public function removeFromSet($member, $set)
	{
		if (!$this->_redis)
			return null;

		if (is_array($member)) {
			if (!count($member))
				return true;
			$redis = $this->_redis;
			$return = call_user_func_array(array($redis, 'sRem'), array_merge(array($this->_keyFromId($set)), $member));
		} else {
			$return = $this->_redis->sRem($this->_keyFromId($set), $member);
		}
		return $return;
	}

	/**
	 * Returns all keys in a Redis set
	 *
	 * @param string $set
	 * @return array member keys of set
	 */
	public function membersInSet($set)
	{
		if (!$this->_redis)
			return null;

		return $this->_redis->sMembers($this->_keyFromId($set));
	}

	/**
	 * Clean some cache records
	 *
	 * Available modes are :
	 *
	 * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
	 *                                               ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
	 *                                               ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
	 *                                               ($tags can be an array of strings or a single string)
	 *
	 * @param string $mode clean mode
	 * @param tags array $tags array of tags
	 * @return boolean true if no problem
	 */
	public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = [])
	{
		return $this->_clean($mode, $tags);
	}

	/**
	 * Clean some cache records (protected method used for recursive stuff)
	 *
	 * Available modes are :
	 * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
	 * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
	 *                                               ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
	 *                                               ($tags can be an array of strings or a single string)
	 * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
	 *                                               ($tags can be an array of strings or a single string)
	 *
	 * @param  string $dir  Directory to clean
	 * @param  string $mode Clean mode
	 * @param  array  $tags Array of tags
	 * @throws Zend_Cache_Exception
	 * @return boolean True if no problem
	 */
	protected function _clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = [])
	{
		if (!$this->_redis)
			return false;

		$result = true;
		$all = [];

		if ($mode == Zend_Cache::CLEANING_MODE_ALL)
			return $this->_redis->flushDb();

		if ($mode == Zend_Cache::CLEANING_MODE_OLD)
			return true; /* Redis takes care of expire */

		if ($mode == Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG && $tags && (is_string($tags) || count($tags)))
			return $this->removeTag($tags);

		if ($mode == Zend_Cache::CLEANING_MODE_MATCHING_TAG && $tags && (is_string($tags) || count($tags) == 1))
			return $this->removeTag($tags);

		if ($mode == Zend_Cache::CLEANING_MODE_MATCHING_TAG && $tags && count($tags))
			return $this->remove($this->getIdsMatchingTags($tags));

		if ($mode == Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG)
			Zend_Cache::throwException('CLEANING_MODE_NOT_MATCHING_TAG not implemented for Redis cache');

		Zend_Cache::throwException('Invalid mode for clean() method');
	}

	/**
	 * Test if the given cache id is available (and still valid as a cache record)
	 *
	 * @param  string  $id                     Cache id
	 * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
	 * @return boolean|mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
	 */
	protected function _test($id, $doNotTestCacheValidity)
	{
		if (!$this->_redis)
			return false;

		if ($doNotTestCacheValidity) {
			return true;
		}
		$tags = $this->_redis->sMembers($this->_keyFromItemTags($id));
		if (!$tags || !count($tags))
			return false;
		foreach ($tags as $tag) {
			if ($tag && !$this->_redis->sIsMember($this->_keyFromTag($tag), $id))
				return false;
		}

		return true;
	}

	/**
	 * Return cached id
	 *
	 * @param string $id cache id
	 * @return string cached datas
	 */
	protected function _load($id)
	{
		if (!$this->_redis)
			return false;

		return $this->_redis->get($this->_keyFromId($id));
	}

	/**
	 * Return an array of stored cache ids. Not implemented for Redis cache
	 *
	 * @throws Zend_Cache_Exception
	 */
	public function getIds()
	{
		Zend_Cache::throwException('Not possible to get available IDs on Redis cache');
	}


	/**
	 * Return an array of stored tags. Not implemented for Redis cache
	 *
	 * @throws Zend_Cache_Exception
	 */
	public function getTags()
	{
		Zend_Cache::throwException('Not possible to get available tags on Redis cache');
	}

	/**
	 * Return an array of stored cache ids which match given tags
	 *
	 * In case of multiple tags, a logical AND is made between tags
	 *
	 * @param array $tags array of tags
	 * @return array array of matching cache ids (string)
	 */
	public function getIdsMatchingTags($tags = [])
	{
		if (!$this->_redis)
			return [];

		if (!$tags)
			return [];
		if ($tags && is_string($tags))
			$tags = array($tags);

		$matchTags = [];
		foreach ($tags as $tag) {
			$matchTags[] = $this->_keyFromTag($tag);
		}
		if (count($matchTags) == 1)
			return $this->_redis->sMembers($matchTags[0]);

		return $this->_redis->sInter($matchTags);
	}

	/**
	 * Return an array of stored cache ids which don't match given tags. Not implemented for Redis cache
	 *
	 * In case of multiple tags, a logical OR is made between tags
	 *
	 * @param array $tags array of tags
	 * @throws Zend_Cache_Exception
	 */
	public function getIdsNotMatchingTags($tags = [])
	{
		Zend_Cache::throwException('Not possible to get IDs not matching tags on Redis cache');
	}

	/**
	 * Return an array of stored cache ids which match any given tags
	 *
	 * In case of multiple tags, a logical AND is made between tags
	 *
	 * @param array $tags array of tags
	 * @return array array of any matching cache ids (string)
	 */
	public function getIdsMatchingAnyTags($tags = [])
	{
		if (!$this->_redis)
			return [];

		if (!$tags)
			return [];
		if ($tags && is_string($tags))
			$tags = array($tags);

		$return = [];
		foreach ($tags as $tag) {
			foreach ($this->_redis->sMembers($this->_keyFromTag($tag)) as $id) {
				$return[] = $id;
			}
		}
		return $return;
	}

	/**
	 * Return the filling percentage of the backend storage. Not implemented on Redis cache
	 *
	 * @throws Zend_Cache_Exception
	 */
	public function getFillingPercentage()
	{
		Zend_Cache::throwException('getFillingPercentage not implemented on Redis cache');
	}

	/**
	 * Return an array of metadatas for the given cache id. Not implemented on Redis cache
	 *
	 * @param string $id cache id
	 * @throws Zend_Cache_Exception
	 */
	public function getMetadatas($id)
	{
		Zend_Cache::throwException('Metadata not implemented on Redis cache');
	}

	/**
	 * Give (if possible) an extra lifetime to the given cache id
	 *
	 * @param string $id cache id
	 * @param int $extraLifetime
	 * @return boolean true if ok
	 */
	public function touch($id, $extraLifetime)
	{
		$ttl = $this->_redis->ttl($id);
		return $this->_redis->setTimeout($id, $ttl + $extraLifetime);
	}

	/**
	 * Increment cache id value
	 *
	 * @param string $id cache id
	 * @param int $offset
	 * @param int $initial
	 * @return boolean $specificLifetime or integer in seconds lifetime
	 */
	public function increment($id, $offset = 1, $initial = 0, $specificLifetime = false)
	{
		$result = $this->_redis->incrBy($id, $offset);
		return $result;
	}

	/**
	 * Decrement cache id value
	 *
	 * @param string $id cache id
	 * @param int $offset
	 * @param int $initial
	 * @return boolean $specificLifetime or integer in seconds lifetime
	 */
	public function decrement($id, $offset = 1, $initial = 0, $specificLifetime = false)
	{
		$result = $this->_redis->decrBy($id, $offset);
		return $result;
	}


	/**
	 * Give (if possible) an extra lifetime to the given cache id (and only that key, no tags are updated)
	 *
	 * @param string $id cache id
	 * @param int $extraLifetime
	 * @return boolean true if ok
	 */
	protected function _touchKey($id, $extraLifetime)
	{
		if (!$this->_redis)
			return false;

		$data = $this->load($id, true);
		if ($data === false)
			return false;
		return $this->storeKey($data, $id, $extraLifetime);
	}

	/**
	 * Return an associative array of capabilities (booleans) of the backend
	 *
	 * The array must include these keys :
	 * - automatic_cleaning (is automating cleaning necessary)
	 * - tags (are tags supported)
	 * - expired_read (is it possible to read expired cache records
	 *                 (for doNotTestCacheValidity option for example))
	 * - priority does the backend deal with priority when saving
	 * - infinite_lifetime (is infinite lifetime can work with this backend)
	 * - get_list (is it possible to get the list of cache ids and the complete list of tags)
	 *
	 * @return array associative of with capabilities
	 */
	public function getCapabilities()
	{
		return array(
			'automatic_cleaning' => true,
			'tags' => true,
			'expired_read' => false,
			'priority' => false,
			'infinite_lifetime' => true,
			'get_list' => false
		);
	}

	/**
	 * @param string $data
	 * @param int $level
	 * @throws Exception
	 * @return string
	 */
	protected function _encodeData($data, $level)
	{
		if ($level && strlen($data) >= $this->_compressThreshold) {
			switch ($this->_compressionLib) {
				case 'snappy':
					$data = snappy_compress($data);
					break;
				case 'lzf':
					$data = lzf_compress($data);
					break;
				case 'gzip':
					$data = gzcompress($data, $level);
					break;
			}
			if (!$data) {
				throw new Exception("Could not compress cache data.");
			}
			return $this->_compressPrefix . $data;
		}
		return $data;
	}

	/**
	 * @param bool|string $data
	 * @return string
	 */
	protected function _decodeData($data)
	{
		if (substr($data, 2, 3) == self::COMPRESS_PREFIX) {
			switch (substr($data, 0, 2)) {
				case 'sn':
					return snappy_uncompress(substr($data, 5));
				case 'lz':
					return lzf_decompress(substr($data, 5));
				case 'gz':
				case 'zc':
					return gzuncompress(substr($data, 5));
			}
		}
		return $data;
	}
}