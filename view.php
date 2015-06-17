<?php
class view
{
	/**
	 * The controller this view belongs to.
	 * @var controller
	 */
	public $controller = null;

	protected function init()
	{
		// Override me with your own constructor (optional).
	}
	
	public function view($controller)
	{
		$this->controller = $controller;
	}
	
	/**
	 * Called by the layout when its time to display the page.
	 * Simply just includes the view php script.
	 */
	public function show($viewFile)
	{
		include_once($viewFile);
	}
	
	public function render($view)
	{
		require(APP_DIR . "/$view");
	}
	
	/**
	 * Gets the note stored from a controller in setNote().
	 * You can pass in a string like <p>%</p>, and we'll
	 * return that string with the note replacing the %.
	 */
	public function getNote($template=false, $key='note')
	{
		$note = isset_val($_SESSION[$key]);
		$_SESSION[$key] = '';
		
		if($note && $template) $note = str_replace('%', $note, $template);
		return $note;
	}
	
	/**
	 * Like getNote, but for errors.
	 */
	public function getError($template=false)
	{
		return $this->getNote($template, 'error');
	}
	
	/**
	 * Returns HTML <script> tags to wrap your JS in.
	 */
	public function addJS($js)
	{
		$html=<<<HTML
<script type="text/javascript">
// <![CDATA[
	$js
// ]]>
</script>
HTML;
		return $html;
	}
}