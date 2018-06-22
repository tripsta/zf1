<?php

/**
 * @see Zend_Session_SaveHandler_Interface
 */
require_once 'Zend/Session/SaveHandler/Interface.php';

class Zend_Session_SaveHandler_Compression extends SessionHandler implements Zend_Session_SaveHandler_Interface
{
	const COMPRESSION_LEVEL = 3;

	const COMPRESSION_THRESHOLD = 1024;

	const COMPRESSION_PREFIX = ":\x1f\x8b";

	private $_compressionLib = null;
	private $_compressPrefix = null;

	function __construct()
	{
		if (function_exists('snappy_compress')) {
			$this->_compressionLib = 'snappy';
		} else {
			$this->_compressionLib = 'gzip';
		}
		$this->_compressPrefix = substr($this->_compressionLib, 0, 2) . self::COMPRESSION_PREFIX;
	}

	/**
	 * Read session data
	 *
	 * @param string $id
	 */
	public function read($id)
	{
		$data = parent::read($id);

		return $this->_decodeData($data);
	}

	/**
	 * Write Session - commit data to resource
	 *
	 * @param string $id
	 * @param mixed $data
	 */
	public function write($id, $data)
	{
		$data = $this->_encodeData($data, self::COMPRESSION_LEVEL);

		return parent::write($id, $data);
	}

	/**
	 * @param string $data
	 * @param int $level
	 * @throws Exception
	 * @return string
	 */
	protected function _encodeData($data, $level)
	{
		if ($level && strlen($data) >= self::COMPRESSION_THRESHOLD) {
			switch ($this->_compressionLib) {
				case 'snappy':
					$data = snappy_compress($data);
					break;
				case 'gzip':
					$data = gzcompress($data, $level);
					break;
			}
			if (!$data) {
				throw new Exception("Could not compress session data.");
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
		if (substr($data, 2, 3) == self::COMPRESSION_PREFIX) {
			switch (substr($data, 0, 2)) {
				case 'sn':
					return snappy_uncompress(substr($data, 5));
				case 'gz':
				case 'zc':
					return gzuncompress(substr($data, 5));
			}
		}
		return $data;
	}
}