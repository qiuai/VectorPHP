<?php

class myDB {
	public $querynum = 0;
	public $cache;
	public $cache_method;
	private $uniqid;
	private $expire;
	private $c;
	public $db;
	private $db_info;
	private $utf8;
	private $command_to_flush = array(
									'delete',
									'update',
									'insert'
								);
	
	public function __construct($data = array(), $cache = false, $memcached = NULL, $expire = 60) {
		if ($cache==true) {
			if (is_array($memcached)) {
				$this->c = new myMemcached($memcached);
				$this->cache = true;
				$this->cache_method = 'Memcached';
				$this->expire = $expire;
			}else{
				$this->c = new myAPC();
				$this->cache = true;
				$this->cache_method = 'APC';
				$this->expire = $expire;
			}
		}else{
			$this->cache = false;
		
		}
		$this->db_info = $data;
		if ($data['utf8'] == true) $this->utf8 = true;
		else $this->utf8 = false;
		$this->uniqid = md5($data['host'].$data['port'].$data['user'].$data['pass'].$data['db'].$data['identifier']);
		$this->enable_magic_quotes();
		$this->connect();
	}
	
	public function connect() {
		$dsn = 'mysql:host='.$this->db_info['host'].';dbname='.$this->db_info['db'].';port='.$this->db_info['port'];
		try{
			if ($this->utf8 === true) {
				$this->db = new PDO($dsn, $this->db_info['user'], $this->db_info['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'", PDO::ATTR_AUTOCOMMIT => false, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false));
			}else{
				$this->db = new PDO($dsn, $this->db_info['user'], $this->db_info['pass'], array(PDO::ATTR_AUTOCOMMIT => false,  PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false));
			}
		}catch(PDOException $e) {
			throw new Exception($e->getMessage());
		}
	}
	
	private function get_cache($sql) {
		$sql = md5($this->uniqid.$sql);
		$result = $this->c->get('myDB_'.$sql);
		if (isset($result['myDB_result_is_false'])) $result = array();
		return $result;
	}
	
	private function write_cache($sql, $value, $expire) {
		$sql = md5($this->uniqid.$sql);
		if ($this->cache_method == 'APC') {
			if (APC::isExhaust('myDB_', 250)) APC::clear('myDB_');
		}
		$this->c->add('myDB_'.$sql, $value, $expire);
	}
	
	public function query($sql) {
		$command = trim(strtolower(substr($sql, 0, 6)));
		$resource = $this->db->query($sql);
		$this->querynum++;
		if ($this->cache === true && in_array($command, $this->command_to_flush)) $this->c->flush();
		return $resource;
	}
	
	public function parse($action, $table, $params) {
		switch (strtolower($action)) {
			case 'insert':
				foreach ($params as $key => $value) {
					$keys .= '`'.$key.'`, ';
					
					if ($value !== NULL) $values .= '\''.$value.'\', ';
					else $values .= '\'\', ';
				}
				$keys = rtrim($keys, ', ');
				$values = rtrim($values, ', ');
				$sql = "insert into `$table` ($keys) values ($values)";
				break;
			case 'select':
				foreach($params as $select => $param) {
					if ( (strlen($select) === 1 and $select == '*') or (strpos($select, '(') !== false) ) {
						$keys = $select;
					}else{
						$_keys = explode(',', trim($select));
						foreach($_keys as $single) {
							$keys .= '`'.trim($single).'`, ';
						}
						$keys = rtrim($keys, ', ');
					}
					if (isset($param['where'])) {
						$_sql = ' where ';
						foreach ($param['where'] as $key => $value) {
							if ($value !== NULL) $_value .= '\''.$value.'\'';
							else $_value .= '\'\'';
							$_sql .= '`'.$key.'` = '.$_value.' and ';
							unset($_value);
						}
						$_sql = rtrim($_sql, ' and ');
					}
					if (isset($param['order'])) {
						foreach($param['order'] as $key => $method) {
							$_sql .= " order by `$key` $method";
						}
					}
					if (isset($param['limit'])) {
						$_sql .= ' limit '.$param['limit'];
					}
					$sql = "select $keys from `$table`".$_sql;
				}
				break;
			default:
				$sql = '';
				break;
		}
		return $sql;
	}
	
	public function fetch($sql, $expire = NULL) {
		if ($this->cache === true) $result = $this->get_cache($sql); else $result = false;
		if ($result === false) {
			$resource = $this->query($sql);
			foreach($resource as $key => $single) {
				$data[] = $single;
			}
			if (method_exists($resource, 'closeCursor')) $resource->closeCursor();
			if ($this->cache === true) {
				if (!is_array($data)) {
					if ($expire !== NULL) $this->write_cache($sql, array('myDB_result_is_false' => true), $expire);
					else $this->write_cache($sql, array('myDB_result_is_false' => true), $this->expire);
					$data = array();
				}else{
					if ($expire !== NULL) $this->write_cache($sql, $data, $expire);
					else $this->write_cache($sql, $data, $this->expire);
				}
			}
			return $data;
		}else{
			return $result;
		
		}
	}
	
	private function enable_magic_quotes() {
		@ini_set('magic_quotes_gpc', 1);
	}

}

class myAPC {
	public function __construct() {
		if (!function_exists('apc_store')) throw new Exception('Fatal Error: Extension "APC" is missing');
		
	}
	public function get($key) {
		return APC::get($key);
	}
	public function add($key, $value, $ttl = 0) {
		APC::set($key, $value, $ttl);
	}
	public function flush() {
		APC::clear('myDB_');
	}
}

class myMemcached {

	public function __construct($array) {
		global $memcache_obj;
		
		if (!function_exists('memcache_connect')) throw new Exception('Fatal Error: Extension "Memcache" is missing');
		$memcache_obj = @memcache_connect($array['host'], $array['port'], 3);
		
		if (!is_object($memcache_obj)) throw new Exception('Incorrect Memcached configuration');
				
	}
	public function add($key, $data, $expire) {
		global $memcache_obj;
				
		memcache_set($memcache_obj, $key, $data, MEMCACHE_COMPRESSED, $expire);
		
		return;
	}
	public function get($key) {
		global $memcache_obj;
		
		$data = memcache_get($memcache_obj, $key);
						
		return $data;
	}
	public function flush() {
		global $memcache_obj;
		
		memcache_flush($memcache_obj);
				
		return;
	}
	public function delete($key) {
		global $memcache_obj;
		
		memcache_delete($memcache_obj, $key, 0);
				
		return;
	}
	public function stat() {
		global $memcache_obj;
		
		return $memcache_obj->getExtendedStats();
	}
	public function close(){
		global $memcache_obj;
		
		memcache_close($memcache_obj);
		
		return;
	}

}

if (!class_exists('APC')){ class APC {

	static public function get($key) {
		return apc_fetch($key);
	}
	
	static public function set($key, $value, $ttl = 0) {
		return apc_store($key, $value, $ttl);
	}
	
	static public function exists($key) {
		return apc_exists($key);
	}
	
	static public function delete($key) {
		apc_delete($key);
	}

	static public function inc($key) {
		if (apc_inc($key) === false) {
			apc_store($key, 0);
			apc_inc($key);
		}
		return;
	}
	
	static public function isExhaust($prefix = 'Raw_', $num = 100) {
		$cachedKeys = new APCIterator('user', '/^'.$prefix.'/', APC_ITER_KEY, $num);
		$c = $cachedKeys->getTotalCount();
		$cachedKeys = NULL;
		if ($c > $num) return true;
		else return false;
	}
	
	static public function clear($prefix = 'Raw_') {
		$cachedKeys = new APCIterator('user', '/^'.$prefix.'/', APC_ITER_KEY);
		apc_delete($cachedKeys);
		$cachedKeys = NULL;
	}
	
	static public function showCache($prefix = 'Raw_') {
        $cachedKeys = new APCIterator('user', '/^'.$prefix.'/', APC_ITER_ALL);

        echo "\nkeys in cache\n-------------\n";
        echo "Total Size: ".$cachedKeys->getTotalSize()."\n";
        foreach ($cachedKeys as $key => $value) {
                echo $key . ' => '; 
                print_r($value);
                echo "\n";
        }
        echo "-------------\n";
        $cachedKeys = NULL;
	}
	
	static public function showAll() {
        $cachedKeys = new APCIterator('user', NULL, APC_ITER_ALL);

        echo "\nkeys in cache\n-------------\n";
        echo "Total Size: ".$cachedKeys->getTotalSize()."\n";
        foreach ($cachedKeys as $key => $value) {
                echo $key . ' => '; 
                print_r($value);
                echo "\n";
        }
        echo "-------------\n";
        $cachedKeys = NULL;
	}

} }