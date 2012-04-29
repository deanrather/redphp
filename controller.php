<?php
	/**
	 * Controllers extend this base controller class.
	 * The core will use it to load the view, and it also 
	 * offers some functionality to your controllers.
	 */
	class controller
	{
		/**
		 * The core
		 * @var core
		 */
		public $core = null;
		
		/**
		 * The view this view will load later on.
		 * @var view
		 */
		public $view = null;
		
		public function controller($core)
		{
			$this->core = $core;
			$this->init();
			
			// Only do the following if we've been extended. (ie; not if someone used "new controller()"
			if(get_class($this) != 'controller' && get_class($this) != 'app')
			{
				if(class_exists('appView')) {
					$this->view = new appView($this);	
				}
				else
				{
					$this->view = new view($this);
				}
				$this->view->init();
				$app = (isset($core->uri[0]) ? $core->uri[0] : 'index');
				$page = 'index';
				if(isset($core->uri[1]) && (!is_numeric($core->uri[1]))) $page = $core->uri[1];
				$action = $page.'View';
				if(!method_exists($this, $action)) $action='defaultView';
				$this->core->pageDetails['view'] = '../app/'.$app.'/'.$app.($page=='index' ? '' : '-'.$page).'View.php';
				$this->$action();
			}
		}
		
		protected function defaultView()
		{
			// Override me with your own default view (optional).
			$page = (isset($this->core->uri[1]) ? $this->core->uri[1] : 'index');
			$page .= 'View()';
			$this->core->error("You need [ <b>$page</b> ] or [ <b>defaultView()</b> ] in this controller.");
		}
		
		protected function init()
		{
			// Override me with your own constructor (optional).
		}
		
		/**
		 * Change which layout this page will display in.
		 */
		public function setLayout($layout)
		{
			$this->core->config['layout'] = $layout;
		}
		
		/**
		 * Set the page's title
		 */
		public function setTitle($title)
		{
			$this->core->pageDetails['title'] = $title;
		}
		
		/**
		 * Add another .js file to the head
		 */
		public function addJS($script)
		{
			$this->core->pageDetails['scripts'] .= '<script type="text/javascript" charset="ISO-8859-1" src="'.$this->core->config['index_dir'].'js/'.$script.'.js"></script>'."\n";
		}
		
		/**
		 * Add another .css file to the head
		 */
		public function addCSS($style)
		{
			$this->core->pageDetails['styles'] .= '<link rel="stylesheet" type="text/css" href="'.$this->core->config['index_dir'].'css/'.$style.'.css" />'."\n";
		}
		
		/**
		 * Returns a newly instantiated table.
		 * @return table
		 */
		public function newTable($table)
		{
			$file = '../app/_tables/'.$table.'Table.php';
			if(!file_exists($file)) $this->core->error("Error including table. [<b> $file </b>] doesn't exist.");
			require_once($file);
			$table = $table.'Table';
			if(!class_exists($table)) $this->core->error("Error opening table. [<b> $file </b>] needs [<b> class $table extends table</b>].");
			return new $table($this);
		}
		
		/**
		 * Redirect to another page.
		 * Defaults to the current page (good for clearing POST)
		 */
		public function redirect($url=false)
		{	
			// check nothings been printed yet
			if(ob_get_contents()) $this->core->error("Shouldn't redirect. There's output.");
			
			// Generate new URL
			$indexDir = $this->core->config['index_dir'];
			if(!$url) $url = str_replace($indexDir,'',$_SERVER['REQUEST_URI']);
			$url = 'http://'.$_SERVER['HTTP_HOST'].$indexDir.$url;
			
			// send them to new url
			header("location:$url");
			exit;
		}
		
		/**
		 * Sets a note to be retrieved by a view's $this->getNotes() function.
		 */
		public function setNote($note='OK')
		{
			if(!session_id()) $this->core->error('You must use session_start(), or have "use_session" enabled in config.ini to use setNote().');
			$existingNote = $_SESSION['note'];
			if($existingNote) $note = $existingNote.'<br>'.$note;
			$_SESSION['note'] = $note;
		}
		
		
		/**
		 * Sets an error to be retrieved by a view's $this->getNotes() function.
		 */
		public function setError($error='Error')
		{
			if(!session_id()) $this->core->error('You must use session_start(), or have "use_session" enabled in config.ini to use setError().');
			$existingError = $_SESSION['error'];
			if($existingError) $error = $existingError.'<br>'.$error;
			$_SESSION['error'] = $error;
		}
		
		/**
		 * Pass me the path to the view. eg. /index/indexView.php
		 *
		 * @param unknown_type $view
		 */
		public function setView($view='')
		{
			$this->core->pageDetails['view'] = "../app$view";
		}
	}
?>