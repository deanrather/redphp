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
				return $this->_table->rowUpdate($data, $this->_key);
			}
			else // Insert
			{
				$this->_key = $this->_table->rowInsert($data);
				return $this->_key;
			}
		}
	}
?>