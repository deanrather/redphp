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
				$this->$col = $value;
			}
			$this->edit_user = $_SESSION['user_id'];
			$this->edit_date = time();
		}
		
		public function getDefault($col) {
			$col = str_replace(' ', '', $this->_table->fancify($col));
			$variable = '_default'.$col;
			return isset_val($this->$variable);
		}
	}
?>