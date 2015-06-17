<?php
	
	class app extends controller
	{
		public $core = null;
		public static $instance = null;
		
		function app($core)
		{
			$this->core = $core;
			//$this->addCSS('/build/css/docs.css');
			$this->addCSS('/build/css/style1.css');
			
			if(!self::$instance) self::$instance=$this;
			
			$this->checkDB();
			$this->checkLoggedIn();
			
			
			ini_set('session.gc_maxlifetime', 60*24*1000);
			ini_set('session.cookie_lifetime', 60*24*1000);
			setcookie('gamehub', 1, time()+60*24*1000);
			
			define("APP_DIR", dirname(__FILE__));
			
			$this->loadPlugins();
			nodeNotifications::startNotificationHandler();
		}
		
		function checkLoggedIn(){
			
			if(isset_val($this->core->uri['0'])=='login') return;
			
			// Set default session variables
			if(!isset_true($_SESSION['logged_in']))
			{
				$_SESSION['logged_in'] = false;
			}
			
			// Get session variables for this page
			$loggedIn	= isset_val($_SESSION['logged_in']);
			
			// Forward them to the appropriate page
			if(!$loggedIn)		$this->redirect('login');
			
		}
		
		
		/**
		 * Automagically keeps the db up to date with changes stored in /db/update-x.sql
		 */
		function checkDB(){
			require_once('dbManager.php');
			$manager = new dbManager($this);
			$manager->checkForUpdates();
		}
		
		function loadPlugins(){
			require_once('plugins/plugin.php');
			require_once('plugins/nodeNotifications/nodeNotifications.php');
		}
	}
?>