<?php
class appController extends controller {

	public $user = null;

	public function init() {
		$table = $this->newTable('user');
		$this->me = $table->getRow(isset_val($_SESSION['user_id']));
	}

	function salt_md5($text)
	{
		return md5($this->core->config['salt'].$text);
	}
}
