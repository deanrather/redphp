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
			foreach($temp as $key => $val)
			{
				if($key[0] != '_') $data[$this->_table->clean($key)] = $this->_table->clean($val);
			}
			
			if($this->_key) // Update
			{
				$this->id = $this->_key;
				$this->_table->rowUpdate($data, $this->_key);
				return $this->id;
			}
			else // Insert
			{
				$this->_key = $this->_table->rowInsert($data);
				$this->id = $this->_key;
				return $this->_key;
			}
		}
		
		public function insertData($data)
		{
			foreach($data as $col => $value) {
				if(!is_array($value)) $this->$col = $value;
			}
			$this->edit_user = $_SESSION['user_id'];
			$this->edit_date = time();
		}
		
		public function getDefault($col) {
			$col = str_replace(' ', '', $this->_table->fancify($col));
			$variable = '_default'.$col;
			return isset_val($this->$variable);
		}
		
		public function attachTable($otherTable, $data){
		
			$thisTable = $this->_table->table;
			$joinTable = $thisTable.'_'.$otherTable;
			
			$this->_table->beginTransaction();
			$myID = $this->id;
			
			// Out with the old
			$query = "DELETE FROM $joinTable WHERE $thisTable = $myID";
			$this->_table->update($query);
			
			$myUserID = $_SESSION['user_id'];
			$now = time();
			
			// In with the new
			$postVar = $otherTable.'-list';
			if(isset_true($data[$postVar]) && is_array($data[$postVar])) {
				foreach($data[$postVar] as $otherTableID) {
					$otherTableID = $this->_table->clean($otherTableID);
					$query = "
						 INSERT INTO `$joinTable`
						 	(`$otherTable`, `$thisTable`,`create_user`,`create_date`,`edit_user`,`edit_date`)
						 VALUES
						 	('$otherTableID', '$myID', '$myUserID', '$now', '$myUserID', '$now')
						";
					$this->_table->update($query);
				}
			}
			$this->_table->endTransaction();
			
		}
		
		public function getAttachedTable($otherTable) {
			
			$thisTable = $this->_table->table;
			$joinTable = $thisTable.'_'.$otherTable;
			
			$otherTableClass = $this->_table->controller->newTable($otherTable);
			
			$where = "$joinTable.$thisTable=$this->id";
			$join = "LEFT JOIN $joinTable ON $joinTable.$otherTable = $otherTable.id";
			
			$result = $otherTableClass->getRows("*, `$otherTable`.id as 'otherTableID'", $where, "$otherTable.id", $join);
			if(!$result) $result = array();
			
			return $result;
		}
	}