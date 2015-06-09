<?php
new core();

class core
{
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
	
	/**
	 * eg: domain.com/[0]/[1]/[2]/
	 */
	public $uri			= array();	// [0] and [1] load a controller and view
	public $config		= array();	// Loaded from /etc/config.ini
	public $pageDetails	= array();	// used from the layout
	public $stats		= array();	// Statistics defined in loadConfig
	public $queries		= array();	// A Log of all the queries
	public $dbase		= null; 	// The mysql dbase resource. Created when it needs to be.
	
	public function Core()
	{
		$this->getIncludes();
		$this->loadConfig();
		$this->sqlDump();
		$this->startSession();
		$this->getURI();
		$this->loadApp();
		$this->loadController();
		$this->display();
		$this->debug();
	}
	
	private function getIncludes()
	{
		require_once('globals.php');
		if(file_exists('../etc/globals.php')) require_once('../etc/globals.php');
		require_once('controller.php');
		require_once('view.php');
		require_once('table.php');
		require_once('row.php');
	}

	/**
	 * The environment is determined as the last part of the url.
	 * Eg website.com is of the "com" environment, whereas website.local is of the "local" environment
	 */
	private function determineEnvironment()
	{
		if(file_exists('../etc/determineEnvironment.php'))
		{
			require_once('../etc/determineEnvironment.php');
			$env = determineEnvironment::determine();
			define('ENV', $env);
			return $env;
		}
		define('ENV', 'live');
		$domain = explode('.', $_SERVER['HTTP_HOST']);
		$lastNode = $domain[sizeof($domain)-1];
		return $lastNode;
	}
	
	/**
	 * Loads your projects ./etc/config.ini file
	 * Sets $this->config, pageDetails, and stats
	 */
	private function loadConfig()
	{
		$environment = $this->determineEnvironment();
		$filename = "../etc/config.{$environment}.ini";
		if(!file_exists($filename)) $filename = '../etc/config.ini';
		if(!file_exists($filename)) $filename = _CONFIG_FILENAME_;
		$this->config = parse_ini_file($filename);
		
		$this->pageDetails['scripts']	= '';
		$this->pageDetails['styles']	= '';
		$this->pageDetails['layout']	= 'default';
		$this->pageDetails['title']		= $this->config['title'];
		$this->pageDetails['googleAnalytics'] = '';
		
		if(isset($this->config['google_analytics_key']))
		{
			$this->pageDetails['googleAnalytics'] = $this->googleAnalytics($this->config['google_analytics_key']);
		}
		
		if(isset_true($this->config['timezone'])) date_default_timezone_set($this->config['timezone']);
		
		$this->stats['queryCount']	= 0;
		$this->stats['updateCount']	= 0;
		$this->stats['execTime']	= microtime();
		
	}
	
	/**
	 * If the sqldump flag is set in the URI, save a copy of the db to ./etc/dbase.sql
	 */
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
	}
	
	private function startSession()
	{
		if(isset($this->config['use_session']) && $this->config['use_session'])
		{
			session_start();
		}
	}
	
	/**
	 * Looks at the URI, cleans it, stores it in $this->uri
	 */
	private function getURI()
	{
		$uri = $_SERVER['REQUEST_URI'];
		$uri = explode('?', $uri);
		$uri = $uri[0];
		$uri = trim($uri,'/');
		$uri = explode('/',$uri);
		// Explode returns an array with 1 empty element, instead of an empty array this line fixes that.
		if(count($uri)==1 && !($uri[0])) $uri = array();
		
		$this->uri = $uri;
	}
	
	/**
	 * Loads your projects' app.php but does not yet call init();
	 */
	private function loadApp()
	{
		if(file_exists('../app/app.php'))
		{
			require_once('../app/app.php');
			$this->app = new app($this);
		}
		if(file_exists('../app/view.php'))
		{
			require_once('../app/view.php');
		}
		if(file_exists('../app/controller.php'))
		{
			require_once('../app/controller.php');
		}
	}
	
	/**
	 * Loads the controller, tells it which page it will load later.
	 * The layout will tell the controller to load the page.
	 */
	private function loadController()
	{
		$app = (isset($this->uri[0]) ? $this->uri[0] : 'index');
		$page = (isset($this->uri[1]) ? $this->uri[1] : 'index');
		$app = str_replace('-', '_', $app);
		$page = str_replace('-', '_', $page);
		$controller = '../app/'.$app.'/'.$page.'Controller.php';
		if (!file_exists($controller)) {
			$controller = "../app/$app/{$app}Controller.php";
			$page = $app;
			if (!file_exists($controller)) {
				$oldController = $controller;
				$controller = '../app/index/defaultController.php';
				$page = 'default';
				if (!file_exists($controller)) {
					$newInstallMsg="<hr />If you're still setting up redphp, perhaps dean should update this error message.";
					$this->error("You need [ <b>$oldController</b> ] or [<b>$controller</b>].$newInstallMsg");
				}
			}
		}
		require_once($controller);
		$controller = $app.'Controller';
		if (!class_exists($controller)) {
			
			$controller = $page.'Controller';
			if (!class_exists($controller)) {
				$this->error("You need [<b>{$app}Controller extends controller </b>] or [ <b>class $controller extends controller</b> ].");
			}
			
		}
		$this->controller = new $controller($this);
	}
	
	/**
	 * Loads the layout.
	 * The layout will tell the controller to load its page.
	 */
	private function display()
	{
		require_once('../app/_layouts/'.$this->pageDetails['layout'].'Layout.php');
	}
	
	private function googleAnalytics($key)
	{
		$return = '';
		
		if($key)
		{
			$return = <<<GOOGLEANALYTICS
<!-- Google Analytics Code -->
<script type="text/javascript"> 
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script> 
<script type="text/javascript"> 
try {
var pageTracker = _gat._getTracker("$key");
pageTracker._trackPageview();
} catch(err) {}</script>
<!-- / Google Analytics Code -->
GOOGLEANALYTICS;
		}
		
		return $return;
	}
	
	/**
	 * If 'debug' is defined in the config, it will print an error message,
	 * Otherwise, it just prints a '404' for the user.
	 */
	public function error($message = '')
	{
		header("Status: 404 Not Found");
		$trace=debug_backtrace();
		$log = '';
		$log.= "<!--\n";
		$log.= "STACK TRACE: ".print_r($trace, true);
		$log.= "POST: ".print_r($_POST, true);
		$log.= "\n--><pre>";
		foreach ($trace as $file) {
			$log .= "\n".$file['file'].' - '.$file['function'].'('.$file['line'].')';
		}
		$log.= "\n</pre>\n<hr />\nError: $message";
			
		if (isset_true($this->config['debug']))
		{
			die($log);
		} else {
			$file = fopen('../etc/error.log', 'a');
			fwrite($file, date('r')." ========================================\n$log\n\n");
			if(file_exists('../app/index/404View.php'))
			{
				$this->controller = new controller($this);
				if (class_exists('appView')) {
					$this->controller->view = new appView($this->controller);	
				} else {
					$this->controller->view = new view($this->controller);
				}
				$this->pageDetails['view'] = '../app/index/404View.php';
				require_once('../app/_layouts/'.$this->pageDetails['layout'].'Layout.php');
				exit;
			} else {
				die('404');
			}
		}
	}
	
	private function debug()
	{
		if (!isset_true($this->config['debug'])) return;
		
		if (isset_true($this->config['queries']) || isset($_GET['queries']))
		{
			print "<hr>All Queries:<pre>";
			foreach($this->queries as $query) echo "\n\n$query";
			print "</pre>";
		}
		
		if(isset_true($this->config['stats']) || isset($_GET['stats']))
		{
			print '<hr />Queries: '.$this->stats['queryCount'];
			print '<br />Updates: '.$this->stats['updateCount'];
			print '<br />Time: '.number_format(microtime()-$this->stats['updateCount'],3);
		}
	}
}