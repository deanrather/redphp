<?php
class dbManager extends table {
	
	public $table = 'website';
	
	function checkForUpdates(){
		$currentVersion = $this->get('db_version');
		$currentVersion = $currentVersion[0];
		
		$file = '../db/update-'. (++$currentVersion). '.sql';
		while(file_exists($file)) {
			
			$this->beginTransaction();
			
			$allQueries = file_get_contents($file);
			$allQueries = explode(';', $allQueries);
			foreach($allQueries as $query) {
				
				if(!trim($query)) continue; // skip blank lines
				
				$result = mysqli_query($this->controller->core->dbase, $query);
				if(!$result) {
					$this->controller->setError("DATABASE UPGRADE TO VERSION $currentVersion FAILED:<br>$query");
					$this->rollBack();
					return;
				}
			}
			
			$query = "UPDATE website SET db_version = $currentVersion";
			$result = mysqli_query($this->controller->core->dbase, $query);
			
			$this->endTransaction();
			
			$this->controller->setNote("DATABASE UPGRADED TO VERSION $currentVersion");
			
			$file = '../db/update-'. (++$currentVersion). '.sql';
		}
	}
	
}