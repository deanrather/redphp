<?php
class editableRow extends row
{
	/**
	 * You can define several:
	 * public $defaultName = 'your name'
	 * public $defaultEmail = 'test@email.com'
	 * etc. here
	 */
	
	/**
	 * You can define several
	 * public function displayName()
	 * public function displayEmail()
	 * etc.. here.
	 */
	
	public function displayAttribute($element){
		return $this->_table->displayAttribute($this, $element);
	}
}