<?php

namespace Core;

use Service\Config;
use Service\Conversation\Post;
use Service\User;

class View
{

	protected $_config = array();
	protected $_currentUser = null;
	protected $_unconfirmedUsers = array();
	protected $_controller;
	protected $_action;
	protected $_templatePath;
	protected $_templateFile;
	protected $_templateVars = array();
	protected $_encoding = 'utf-8';
	protected $_layout = null;
	protected $_rendered = false;
	protected $translator;

	private $_unreadConversationCount = null;

	public function __construct($controller, $action, $templatePath, $templateFile, $translator)
	{
		$this->_controller = $controller;
		$this->_action = $action;
		$this->_js = false;

		$this->_config = Config::getAll();
		$this->_currentUser = User::getCurrent();
		$this->_templatePath = $templatePath;
		$this->_templateFile = $templateFile;
		$this->_layout = APPLICATION_ROOT . '/application/templates/layouts/default.phtml';
		$this->translator = $translator;

		if ($this->_currentUser && $this->_currentUser->getType() === 'admin') {
			$this->_unconfirmedUsers = User::getUnconfirmedUsers();
		}

		$jsPath = '/js/' . $controller . '/' . $action . '.js';

		if (is_file(APPLICATION_ROOT . '/public' . $jsPath)) {
			$this->_js = $jsPath;
		}
	}

	public function __set($name, $value)
	{
		$this->_templateVars[$name] = $value;
	}

	public function __get($name)
	{
		return @$this->_templateVars[$name];
	}

	public function render($templateFile)
	{
		$this->_templateFile = $templateFile;
	}

	public function _render($disableLayout)
	{
		if (!$this->_rendered)
		{
			$this->_rendered = true;

			ob_start();

			extract($this->_templateVars);

			include($this->_templatePath . $this->_templateFile);

			$html = ob_get_clean();

			if (!$disableLayout) {
				ob_start();
				include($this->_layout);
				$html = ob_get_clean();
			}

			return $html;
		}
	}

	protected function escape($var)
	{
		return htmlspecialchars($var, ENT_COMPAT, $this->_encoding);
	}

	protected function displayMessage($aMessage) {

		if (is_array($aMessage)) {
			return '<div class="alert alert-' . @$aMessage['class'] . '"><p>' . $this->_(@$aMessage['message']) . '</p></div>';
		}

	}

	protected function _($key)
	{
		return $this->escape($this->translator->translate($key));
	}

	protected function getUnreadConversationPostCount()
	{
		if ($this->_unreadConversationCount === null) {
			$this->_unreadConversationCount = Post::getUnreadCount();
		}

		return $this->_unreadConversationCount;
	}

	protected function shorten($input, $maxLength)
	{
		if (strlen($input) > ($maxLength - 3)) {
			$input = substr($input, 0, $maxLength - 3) . '...';
		}

		return $input;
	}

}