<?php
	class row
	{
		/**
		 * The table object who created this row.
		 * @var table
		 */
		public $_table	= null;
		
		public $_key = 0;	// If this row was SELECTed, or has been INSERTed, then we should know its id.
		
		function row($table)
		{
			$this->_table = $table;
		}
		
		/**
		 * Saves this row, then returns it for you.
		 * @return row
		 */
		public function save()
		{
			// Ignore class variables
			$temp = get_object_vars($this);
			$data = array();
			$foreignTables = array();
			foreach($temp as $key => $val)
			{
				if($key[0] != '_' && !is_array($val) ) {
					$data[$this->_table->clean($key)] = $this->_table->clean($val);
				}
				
				// Check for foreign tables
				if(is_array($val) && substr($key, -5) == '-list') {
					$foreignTables[substr($key, 0, -5)] = $val;
				}
			}
			
			if($this->_key) // Update
			{
				$this->id = $this->_key;
				$this->_table->rowUpdate($data, $this->_key);
			}
			else // Insert
			{
				$this->_key = $this->_table->rowInsert($data);
				$this->id = $this->_key;
			}
			
			// Update foreign tables
			foreach($foreignTables as $table => $data) $this->attachTable($table, $data);
			
			return $this->id;
		}
		
		public function insertData($data)
		{
			foreach($data as $col => $value) {
				$this->$col = trim($value);
			}
			$this->edit_user_id = $_SESSION['user_id'];
			$this->edit_date = time();
		}
		
		public function getDefault($col) {
			if(right(3, $col) == '_id') $col = right(-3, ($col));
			$col = str_replace(' ', '', $this->_table->fancify($col));
			$variable = '_default'.$col;
			$default = false;
			if(isset($this->$variable)) $default = $this->$variable;
			return $default;
		}
		
		public function attachTable($otherTable, $data){
		
			$thisTable = $this->_table->table;
			$joinTable = $thisTable.'_'.$otherTable;
			$thisTableIDCol = $thisTable.'_id';
			$otherTableIDCol = $otherTable.'_id';			
			
			$this->_table->beginTransaction();
			$myID = $this->id;
			
			// Out with the old
			$query = "DELETE FROM $joinTable WHERE $thisTableIDCol = $myID";
			$this->_table->update($query);
			
			$myUserID = $_SESSION['user_id'];
			$now = time();
			
			// In with the new
			foreach($data as $otherTableID) {
				$otherTableID = $this->_table->clean($otherTableID);
				$query = "
					 INSERT INTO `$joinTable`
					 	(`$otherTableIDCol`, `$thisTableIDCol`,`create_user_id`,`create_date`,`edit_user_id`,`edit_date`)
					 VALUES
					 	('$otherTableID', '$myID', '$myUserID', '$now', '$myUserID', '$now')
					";
				$this->_table->update($query);
			$this->_table->endTransaction();
			}
			
		}
		
		public function getAttachedTable($otherTable) {
			
			$thisTable = $this->_table->table;
			$joinTable = $thisTable.'_'.$otherTable;
			
			$otherTableClass = $this->_table->controller->newTable($otherTable);
			
			$thisTableIDCol = $thisTable.'_id';
			$otherTableIDCol = $otherTable.'_id';
			
			$where = "$joinTable.$thisTableIDCol=$this->id";
			$join = "LEFT JOIN $joinTable ON $joinTable.$otherTableIDCol = $otherTable.id";
			
			$result = $otherTableClass->getRows("`$otherTable`.*", $where, "$otherTable.id", $join);
			if(!$result) $result = array();
			
			return $result;
		}
	}