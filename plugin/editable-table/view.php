<?php
class appView extends view {

	function init(){
		$this->me = $this->controller->me;
	}
	
	function displayMessages(){
		echo $this->getError('<div class="alert alert-error"><a class="close" data-dismiss="alert">x</a><p><b>!</b> %</p></div>');
		echo $this->getNote('<div class="alert alert-success"><a class="close" data-dismiss="alert">x</a><p><b>&#10003;</b> %</p></div>');
	}

	function userURL($id){
		$table = $this->controller->newTable('user');
		$username = $table->getUsername($id);
		return "<a href='/user/$username'>$username</a>";
	}
	
	function timeSince($timestamp){
		$full = date("Y-m-d H:i:s", $timestamp);
		return "<a href='#' rel='tooltip' title='$full'>".$this->howLongAgo($timestamp)."</a>";
	}
	
	function howLongAgo($timestamp){
		$age = time() - $timestamp;
		
		if($age < 60*2) return "just now";
		if($age < 60*60) return round(floor(($age/60)),0)." minutes ago";
		if($age < 60*60*2) return "an hour ago";
		
		// maybe it's 10am and 15hrs ago was yesterday
		$hoursToday = date('H');
		$minsToday = date('i');
		$timeToday = ($hoursToday * 60 * 60 ) + ($minsToday * 60);
		
		if($age < $timeToday) return round(floor(($age/60/60)),0)." hours ago";
		if($age < $timeToday + (60*60*24)) return date("ga", $timestamp). " yesterday";
		if($age < 60*60*24*7) return date('ga l', $timestamp);
		if($age < 60*60*24*7*2) return date('ga', $timestamp)." last ".date('l', $timestamp);
		if($age < 60*60*24*7*4) return round(floor(($age/60/60/24/7)),0)." weeks ago";
		if($age < 60*60*24*7*4*2) return "last month";
		if($age < 60*60*24*7*12) return round(floor(($age/60/60/24/7/4)),0)." months ago";
		if($age < 60*60*24*7*12*2) return "last year";
		else return round(ceil(($age/60/60/24/7/4/12)),0)." years ago";
	}
	

	/**
	 * Like include() except that it returns the content instead of displaying it.
	 * @param $filename
	 */
	function get_include_contents($filename) {
	    if (is_file($filename)) {
	        ob_start();
	        include $filename;
	        $return = ob_get_clean();
	        return $return;
	    }
	    return false;
	}

	public function markdown($content){
		require_once('../lib/php-markdown.php');
		$content = Markdown($content);
		$content = str_replace('{user}', ucfirst($this->controller->me->username), $content);
		return $content;
	}

	public function comments($tableName, $tableID) {
		
		$table = $this->controller->newTable('comment');
		
		$this->table = $tableName;
		$this->table_id = $tableID;
		$this->comments = $table->getComments($tableName, $tableID);
		
		require(APP_DIR.'/comments/commentsView.php');
	}
	
}