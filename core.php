<?php	new core();		class core	{
		/**
		 * User's optional app class
		 * @var controller
		 */
		public $app = null;
		
		/**
		 * The controller for this page
		 * @var controller
		 */
		public $controller = null;
				public $config		= array();	// Loaded from /etc/config.ini		public $uri			= array();	// [0] and [1] load a controller and view		public $pageDetails	= array();	// used from the layout
		public $stats		= array();	// Statistics defined in loadConfig
		public $dbase		= null; 	// The mysql dbase resource. Created when it needs to be.
				public function Core()		{			$this->getIncludes();			$this->loadConfig();
			$this->sqlDump();			$this->startSession();			$this->getURI();
			$this->loadApp();			$this->loadController();			$this->display();
			if(isset_true($this->config['debug']) && isset_true($this->config['stats']))
			{
				print '<hr />Queries: '.$this->stats['queryCount'];
				print '<br />Updates: '.$this->stats['updateCount'];
				print '<br />Time: '.number_format(microtime()-$this->stats['updateCount'],3);
			}		}				private function getIncludes()		{
			require_once('globals.php');			if(file_exists('../etc/globals.php')) require_once('../etc/globals.php');
			require_once('controller.php');			require_once('view.php');
			require_once('table.php');
			require_once('row.php');		}				private function loadConfig()		{			$this->config = parse_ini_file('../etc/config.ini');
			
			$this->pageDetails['scripts']	= '';
			$this->pageDetails['styles']	= '';
			$this->pageDetails['layout']	= 'default';
			$this->pageDetails['title']		= $this->config['title'];
			
			$this->stats['queryCount']	= 0;
			$this->stats['updateCount']	= 0;
			$this->stats['execTime']	= microtime();		}
		
		private function sqlDump()
		{
			// Check it's ok.
			if(
				isset_true($this->config['debug'])
				&& isset_true($this->config['mysql_dir'])
				&& isset($_GET['sqldump'])
			)
			{
				// Explain what's going on.
				print 'Because debug mode is enabled, you\'ve ';
				print 'configured your mysql_dir in config, and passed in ?sqldump; ';
				print 'we\'re doing an sql dump now.<hr />';
				ob_flush();
				flush();
				
				// Dump
				$table = new table(new controller($this));
				$table->sqlDump();
			}
		}				private function startSession()		{			if(isset($this->config['use_session']) && $this->config['use_session'])			{				session_start();			}		}
				/**		 * Looks at the URI, cleans it, stores it in $this->uri		 * Also decides which controller (app) and view (page) to load.		 */		private function getURI()		{			$uri = $_SERVER['REQUEST_URI'];			$uri = str_replace($this->config['index_dir'],'',$uri);			$uri = trim($uri,'/');			$uri = explode('/',$uri);			// Explode returns an array with 1 empty element, instead of an empty array this line fixes that.			if(count($uri)==1 && !($uri[0])) $uri = array();						$this->uri = $uri;		}
		
		private function loadApp()
		{
			if(file_exists('../app/app.php'))
			{
				require_once('../app/app.php');
				$this->app = new app($this);
			}
		}				/**		 * Loads the controller, tells it which page it will load later.		 * The layout will tell the controller to load the page.		 */		private function loadController()		{
			$app = (isset($this->uri[0]) ? $this->uri[0] : 'index');
			$controller = '../app/'.$app.'/'.$app.'Controller.php';			if(!file_exists($controller)) {
				$oldController = $controller;
				$controller = '../app/index/defaultController.php';
				$app = 'default';
				if(!file_exists($controller)) {
					$this->error("You need [ <b>$oldController</b> ] or [<b>$controller</b>].");
				}
			}
			require_once($controller);			$controller = $app.'Controller';			if(!class_exists($controller)) $this->error("You need [ <b>class $controller extends controller</b> ].");			$this->controller = new $controller($this);
		}				/**		 * Loads the layout.		 * The layout will tell the controller to load its page.		 */		private function display()		{			require_once('../app/_layouts/'.$this->pageDetails['layout'].'Layout.php');		}				/**		 * If 'debug' is defined in the config, it will print an error message,		 * Otherwise, it just prints a '404' for the user.		 */		public function error($message = '')		{
			$log = '';
			$log.= "<!--\n";
			$trace=debug_backtrace();
			$log.= print_r($trace, true);
			$log.= "\n--><pre>";
			foreach($trace as $file)
			{
				$log .= "\n".$file['file'].' - '.$file['function'].'('.$file['line'].')';
			}
			$log.= "\n</pre>\n<hr />\nError: $message";
				
			if(isset_true($this->config['debug']))
			{
				die($log);
			}
			else
			{
				$file = fopen('../etc/error.log', 'a');
				fwrite($file, date('r')." ========================================\n$log\n\n");
				if(file_exists('../app/index/404View.php'))
				{
					$this->controller = new controller($this);
					$this->controller->view = new view($this->controller);
					$this->pageDetails['view'] = '../app/index/404View.php';
					require_once('../app/_layouts/'.$this->pageDetails['layout'].'Layout.php');
					exit;
				}
				else
				{
					die('404');
				}
			}		}	}?>