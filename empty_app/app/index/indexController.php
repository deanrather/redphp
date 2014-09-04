<?php
class indexController extends Controller
{
	function indexView()
	{
		$this->view->message = 'Hello World.';
	}
}