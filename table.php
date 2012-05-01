<?php
	class table
	{
		/**
		 * A pointer to the controller who made me. (it has a core)
		 * @var controller
		 */
		public $controller = null;
		
		public $table = '';			// The table name of this table
		public $key = '';			// The primary key of this table
		private static $instance = null;	// an instance of this class
		private $cacheNextQuery = false; // use cacheNextQuery() before doing query and it will be cached or returned from cache
		private $cache = array();
		
		
		/**
		 * The last executed query;
		 */
		public $lastQuery;
		
		/**
		 * The result of the last executed query;
		 */
		public $lastResult;
		
		public function table($controller)
		{
			$this->controller = $controller;
			$this->init();
		}
		
		public static function getInstance()
		{
			if(self::$instance === null) self::$instance = new table();
			return self::$instance;
		}
		
		protected function init()
		{
			// Override me with your own constructor.
		}
		
		private function connectDB()
		{
			if(is_null($this->controller->core->dbase))
			{
				if(isset_true($this->controller->core->config['dbase_host']))
				{
					$this->controller->core->dbase = mysqli_connect
					(
						$this->controller->core->config['dbase_host'],
						$this->controller->core->config['dbase_user'],
						$this->controller->core->config['dbase_pass']
					);
					
					if(!mysqli_select_db($this->controller->core->dbase, $this->controller->core->config['dbase_dbase']))
					{
						$this->controller->core->error('Cannot connect to database.');
					}
				}
				else
				{
					$this->controller->core->error('Database not configured.');
				}
			}
		}
		
		/**
		 * $query will be queried to the database, this function should be used for SELECTs
		 * $depth determines wether to return a 2-dimensional array, an array which is a row, col, 
		 * or just the single value
		 * $depth can be "ALL", "COL", "ROW", or "CELL".
		 * returns empty array if empty
		 */
		public function query($query, $depth='ALL', $checkSelect=true)
		{
			if($this->cacheNextQuery && isset($this->cache[md5($query)])) return $this->cache[md5($query)];
			
			$this->connectDB();
			if($checkSelect && substr(trim($query), 0, 6) != 'SELECT') $this->controller->core->error('query() wants a SELECT query. not<br />'.$query);
			
			$this->lastQuery  = $query;
			$this->debug($query);
			$result = mysqli_query($this->controller->core->dbase, $query);
			if(!$result) $this->controller->core->error("Query Failed.<hr /><pre>$query");
			$count = mysqli_num_rows($result);
			$data = array();
			for ($i=0; $i < $count; $i++)
			{
				if($depth=='COL' || $depth=='CELL')
				{
					$row = mysqli_fetch_array($result, MYSQL_NUM);
					$data[] = $row[0];
				}
				else // ALL or ROW
				{
					$data[] = mysqli_fetch_array($result, MYSQL_ASSOC);
				}
			}
			
			// If we're just returning 1 row, just return that one row
			if($depth=='ROW' && count($data)) $data = $data[0];
			
			// If we're just returning one cell, just get that piece of data.
			if($depth=='CELL')
			{
				if(count($data))
				{
					$data = $data[0];
				}
				else
				{
					$data = '';
				}
			}
			
			$this->controller->core->stats['queryCount']++;
			if($this->cacheNextQuery) return $this->cache[md5($query)] = $data;
			return $data;
		}
		
		/**
		 * Use this to run UPDATE queries on the database
		 * It will return the number of affected rows
		 */
		public function update($query)
		{
			$this->connectDB();
			$this->lastQuery = $query;
			$this->debug($query);
			$result = mysqli_query($this->controller->core->dbase, $query);
			$this->lastResult = $result;
			if(!$result) $this->controller->core->error("Query Failed.<hr /><pre>$query");
			$this->controller->core->stats['updateCount']++;
			return mysqli_affected_rows($this->controller->core->dbase);
		}
		
		/**
		 * Cleans a string. should be used before entering data into a query.
		 */
		public function clean($string){
			return mysqli_real_escape_string($this->controller->core->dbase, $string);
		}
		
		/**
		 * Use this to run INSERT queries on the database
		 * It will return the ID of the last inserted row
		 */
		public function insert($query)
		{
			$this->connectDB();
			$this->debug($query);
			$result = mysqli_query($this->controller->core->dbase, $query);
			if(!$result) $this->controller->core->error("Query Failed.<hr /><pre>$query");
			$this->controller->core->stats['updateCount']++;
			return mysqli_insert_id($this->controller->core->dbase);
		}
		
		/**
		 * Param 1 can be 1 col eg: "name", or several eg: "name, email"
		 * Param 2 depends on the datatype you give it:
		 * Default:	"WHERE 1" n rows
		 * Integer:	"WHERE primary_key = <yourInt>" 1 row
		 * String:	"WHERE <yourString>" n rows
		 */
		public function get($cols='*', $where=null, $sort=1, $join='')
		{
			if($this->table == '') $this->controller->core->error('Tables need the $table set.');
			if($cols=='*' && $join) $this->controller->core->error('You shouldnt select for * when using a join. Its likely some columns will overlap.');
			
			$depth = 'ALL';
			if(!strstr($cols, '*') && !strpos($cols,',')) $depth = 'COL';
			
			if($where == null)
			{
				$where = 1;
			}
			elseif(is_numeric($where))
			{
				if($this->key == '') $this->controller->core->error($this->table.' table need the $key set to use get(string, int).');
				$where = "$this->key = $where";
				if($depth=='COL')
				{
					$depth = 'CELL';
				}
				else
				{
					$depth = 'ROW';
				}
			}
			$query = "SELECT $cols FROM `$this->table` $join WHERE $where ORDER BY $sort;";
			return $this->query($query, $depth);
		}
		
		public function getCount($where=1)
		{
			if($this->table == '') $this->controller->core->error('Tables need the $table set.');
			$return = $this->query("SELECT COUNT(1) FROM `$this->table` WHERE $where", 'CELL');
			return $return;
		}
		
		/**
		 * Returns the number of affected rows
		 * Param 1 must be the col name
		 * Param 2 must be the val to set it to
		 * Param 3 depends on the datatype you give it:
		 * Default:	"WHERE 1"
		 * Integer:	"WHERE primary_key = <yourInt>"
		 * String:	"WHERE <yourString>"
		 */
		public function set($what=false, $to=false, $where=null)
		{
			$return = false;
			if($what && $to)
			{
				if($where == null)
				{
					$where = 1;
				}
				elseif(is_numeric($where))
				{
					if($this->key == '') $this->controller->core->error($this->table.' table need the $key set to use set(string, string, int).');
					$where = "$this->key = $where";
				}
				$return = $this->update("UPDATE $this->table SET $what = $to WHERE $where;");
			}
			return $return;
		}
		
		/**
		 * Get a list of tables in this database.
		 */
		public function getTables()
		{
			$dbase = $this->controller->core->config['dbase_dbase'];
			$sql = "show table status from `$dbase` where engine is not NULL";
			return $this->query($sql,'COL',false);
		}
		
		/**
	 * Assuming the mysql bin directory is configured in the config.ini,
	 * This will try its luck at doing a mysql dump.
	 */
		public function sqlDump()
		{
			if(!isset_true($this->controller->core->config['mysql_dir']))
			{
				$error = 'mysql_dir needs to be defined in your config.ini.';
				$error.= '<br />eg.: mysql_dir = "C:\\programming\\php\\wamp\\bin\\mysql\\mysql5.0.45\\bin\\"';
				$this->controller->core->error($error);
			}
			elseif(!isset_true($this->controller->core->config['dbase_host']))
			{
				$this->controller->core->error('dbase details need to be defined in your config.ini.');
			}
			else
			{
				$dbase = $this->controller->core->config['dbase_dbase'];
				$host = '-h'.$this->controller->core->config['dbase_host'];
				$user = '-u'.$this->controller->core->config['dbase_user'];
				$mysqldump = $this->controller->core->config['mysql_dir'].'mysqldump.exe';
				$pass = ($this->controller->core->config['dbase_pass'] ? '-p'.$this->controller->core->config['dbase_pass'] : '');
				$options = '--add-drop-database';
				if($user == 'root') $options .= ' --lock-all-tables';
				
				$string = "$mysqldump $host $user $pass $options $dbase";
				print $string.'<br />';
				
				print 'DUMPING...';
				ob_flush();
				flush();
				
				$data = `$string`; // Like system(), but returns string and doesn't print.
				print ($data ? 'DUMP OK<br />' : 'DUMP ERROR<br />');
				
				print 'WRITING...';
				ob_flush();
				flush();
				
				$h = fopen('../etc/dbase.sql','w');
				$success = fwrite($h, $data);
				print ($success ? 'WRITE OK' : 'WRITE ERROR');
				
				if($success) print "<hr /><pre>$data</pre>";
			}
			exit; // We don't want them sqlDumping again accidentally.
		}
		
		// Row functions
		
		/**
		 * Returns a new empty row belonging to this table.
		 * @return row
		 */
		public function createRow($userID = false)
		{
			$file = "../app/_tables/".$this->table.'Row.php';
			if(file_exists($file)) {
				require_once($file);
				$class = $this->table.'Row';
				$row = new $class($this);
			}
			else
			{
				$row =  new row($this);
			}
			
			if($userID){
				$row->create_user = $userID;
				$row->create_date = time();
			}
			
			return $row;
		}
		
		/**
		 * Gets a row of this table, returns the row object.
		 * @return row
		 */
		public function getRow($id=0)
		{
			$row = $this->createRow();
			$row->_key = $id;
			$data = $this->get('*',$id);
			if(sizeof($data)==1) $data = $data[0];
			foreach($data as $key => $val) $row->$key = $val;
			return $row;
		}
		
		/**
		 * Gets several rows of this table, returns the row objects in an array.
		 * @return unknown array(row)
		 */
		public function getRows($cols='*', $where=null, $sort=1, $join='')
		{
			$data=$this->get($cols, $where, $sort, $join);
			$rows = array();
			foreach($data as $rowArray)
			{
				$rowObject = $this->createRow();
				foreach($rowArray as $key => $val) $rowObject->$key = $val;
				$rows[] = $rowObject;
			}
			return $rows;
		}
		
		/**
		 * Used by the row class. do not use.
		 */
		public function rowInsert($data = array())
		{
			$sql = "INSERT INTO `$this->table` (`";
			$sql .= implode('`, `',array_keys($data));
			$sql .= '`) VALUES ("';
			$sql .= implode('", "',$data);
			$sql .= '");';
			return $this->insert($sql);	
		}
		
		/**
		 * Used by the row class. do not use.
		 */
		public function rowUpdate($data=array(), $keyVal=0)
		{
			$sql = "UPDATE `$this->table` SET ";
			$elements = array();
			foreach ($data as $key => $val) $elements[] = "`$key`='$val'";
			$sql .= implode(', ', $elements);
			$sql .= " WHERE `$this->key` = '$keyVal'";
			return $this->update($sql);
		}
		
		private function debug($query) {
		
			if(!$this->controller->core->config['debug']) return;
			
			$trace=debug_backtrace();
			$i=0;
			$file = $trace[$i];
			while(strstr($file['file'], 'redphp')) {
				$file = $trace[++$i];
			}
			$string = $file['file'].' ('.$file['line']."):\n";
			$this->controller->core->queries[] = $string . $query;
			
		}
		
		public function beginTransaction(){
			$this->connectDB();
			mysqli_autocommit($this->controller->core->dbase, false);
		}
		
		public function rollBack(){
			$this->connectDB();
			mysqli_rollback($this->controller->core->dbase);
			mysqli_autocommit($this->controller->core->dbase, true);
		}
		
		public function endTransaction(){
			$this->connectDB();
			mysqli_commit($this->controller->core->dbase);
			mysqli_autocommit($this->controller->core->dbase, true);
		}
		
		public function cacheNextQuery(){
			$this->cacheNextQuery = true;
		}
	
	
		/**
		 * Takes a column name like "email_address" and fancifies it to "Email Address"
		 */
		public function fancify($str){
			$str = str_replace('_', ' ', $str);
			$str = ucwords($str);
			return $str;
		}
	
		/**
		 * Takes a fancy column name like "Email Address" and unfancifies it to "email_address"
		 */
		public function unfancify($str){
			$str = str_replace(' ', '_', $str);
			$str = strtolower($str);
			return $str;
		}
	}
?>