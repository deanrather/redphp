<?php
class editableController extends appController {
	
	// extend this so I know which table I am.
	// if I am fooController, then this should be foo
	public $tableName = '';
	
	function indexView(){
		$this->addJS('/build/js/tableEditor.js');
		
		$table = $this->newTable($this->tableName);
		
		$cols = isset_default($this->cols, '*');
		$where = isset_default($this->where, "true");
		$join = isset_val($this->join);
		$sort = isset_default($this->sort, 'id DESC');
		$itemsPerPage = isset_default($this->itemsPerPage, 50);
		
		
		$this->view->table = $table;
		$this->view->table->itemsPerPage = $itemsPerPage;
		$this->view->{$this->tableName} = $table->getRows($cols, $where, $sort, $join, $itemsPerPage);
	}
	
	function partialView() {
		$data = isset_val($_POST);
		if(!$data) die('error1');
		if(!sizeof($data)) die('error2');
		
		$table = $this->newTable($this->tableName);
		
		$offset = $table->clean($data['offset']);
		$limit = $table->clean($data['limit']);
		
		$rows = $table->getRows('*', 'true', 'id desc', '', $limit, $offset);
		foreach($rows as $row) echo $table->displayRow($row);
		echo $table->paginationRow($offset+$limit, $limit);
		exit;
	}
	
	function editView(){
		$this->addJS('/build/js/tableEditor.js');
		$table = $this->newTable($this->tableName);
		
		// CREATE / UPDATE
		if(sizeof($_POST)){
			
			// Create / Get the row object, & fill it with POST data
			$id = $_POST['id'];
			if($id) {
				$row = $table->getRow($id);
			}
			else
			{
				$row = $table->createRow($_SESSION['user_id']);
			}
			
			// Dont change the unchanged...
			$password = isset_val($_POST['password']);
			if($password)
			{
				if($password == '~unchanged~')
				{
					unset($_POST['password']);
				}
				else
				{
					$_POST['password'] = $this->salt_md5($password);
				}
			}
			
			// Fill it with POST data
			$row->insertData($_POST);
			
			// Save it
			$row->id = $row->save();
			
			
			// Notification for others
			$username = $this->view->userURL($_SESSION['user_id']);
			$done = ($id ? 'edited' : 'created');
			$fancyTableName = $table->fancify($this->tableName);
			$notification = "$username has $done $fancyTableName <a href='/{$this->tableName}/$row->id'>$row->name</a>.";
			
			// If it was a quick-save, return the row formatted like a TR
			if(!isset_true($_POST['_detailView'])) {
				echo $table->displayRow($row);
				exit;
			}
			
			// Set a note for the user and notification for others. Redirect the user
			if(isset_true($_POST['_quickSave'])) {
				$this->setNote("$row->name has been saved. <a href='/{$this->tableName}/$row->id'>click here to view</a>", $notification);
				$this->redirect("/{$this->tableName}/edit/");
			} else  {
				$this->setNote("$row->name has been saved.</a>", $notification);
				$this->redirect("/{$this->tableName}/$row->id");
			}
		}
		
		// GET
		$id = isset_val($this->core->uri[2]);
		if($id) {
			$id = $table->clean($this->core->uri[2]);
			$this->view->{$this->tableName} = $table->getRow($id);
		} else {
			$this->view->{$this->tableName} = $table->createRow();
			$this->view->{$this->tableName}->id = 0;
		}
	}
	
	function getView(){
		$table = $this->newTable($this->tableName);
		$id = $table->clean($_POST['id']);
		$row = $table->getRow($id);
		echo $table->editRow($row);
		exit;
	}
	
	function defaultView(){
		$this->setView("/{$this->tableName}/{$this->tableName}-detailView.php");
		$table = $this->newTable($this->tableName);
		$id = $table->clean($this->core->uri[1]);
		$this->view->{$this->tableName} = $table->getRow($id);
	}
	
}