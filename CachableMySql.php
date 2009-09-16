<?php

require_once('MysqlDatabase.php');

class CachableMysql extends MysqlDatabase {
	function FetchAll($sql, $use_table_names = true, $use_cache = false, $cache_timeout = 180, $custom_cache_key = false) {
		if($use_cache == false) {
			return parent::FetchAll($sql, $use_table_names);
		}
		
		$start = microtime_float();
		
		$cache_key = md5(($custom_cache_key != false ? $custom_cache_key : $sql));
		
		$data = @g('memcache')->get($cache_key);
		if($data == false) {
			// the cache is empty, get it from the db
			if($this->Query($sql) == true) {
				$data = $this->GetDataArray($use_table_names);
				$num_results = parent::getTotalResults();
				// save to cache
				@g('memcache')->add($cache_key, $data, $cache_timeout);
				@g('memcache')->add($cache_key ."-length", $num_results, $cache_timeout);
			}
		} else {
			$end = microtime_float();
			@ObjectCache('framework')->debug_data['queries_used'] += 1;
			@ObjectCache('framework')->debug_data['queries_list'][] = array('string' => $sql, 'backtrace' => debug_backtrace(), 'execution_time' => ($end - $start), 'cached' => true);
			$this->_last_query = $sql;
		}
		
		return $data;
	}

	function FetchOne($sql, $use_table_names = true, $use_cache = false, $cache_timeout = 180, $custom_cache_key = false) {
		if($use_cache == false) {
			return parent::FetchOne($sql, $use_table_names);
		}
		
		$start = microtime_float();
		
		$cache_key = md5(($custom_cache_key != false ? $custom_cache_key : $sql));
		
		$data = @g('memcache')->get($cache_key);
		if($data == false) {
			// the cache is empty, get it from the db
			if($this->Query($sql) == true) {
				$data = $this->MoveNext($use_table_names);
				// save to cache
				@g('memcache')->add($cache_key, $data, $cache_timeout);
			}
		} else {
			$end = microtime_float();
			@ObjectCache('framework')->debug_data['queries_used'] += 1;
			@ObjectCache('framework')->debug_data['queries_list'][] = array('string' => $sql, 'backtrace' => debug_backtrace(), 'execution_time' => ($end - $start), 'cached' => true);
			$this->_last_query = $sql;
		}
		
		return $data;
	}
	
	function InvalidateCache($custom_cache_key) {
		$cache_key = md5($custom_cache_key);
		// the cache is empty, get it from the db
		g('memcache')->delete($cache_key);
	}
	
	function getTotalResults($cache_key = null) {
		if($cache_key != null) {
			$data = @g('memcache')->get(md5($cache_key) ."-length");

			if($data != false) {
				return $data;
			}
		}
		
		return parent::getTotalResults();
	}
}

?>