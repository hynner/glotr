<?php

/**
 * Navigation
 *
 * @author Jan Marek
 * @license MIT
 */

namespace Navigation;

use Nette\Application\UI\Control;

class Navigation extends Control
{

	/** @var NavigationNode */
	private $homepage;

	/** @var NavigationNode */
	private $current;

	/** @var bool */
	private $useHomepage = false;

	/** @var string */
	private $menuTemplate;

	/** @var string */
	private $breadcrumbsTemplate;

	private $translator;
	/**
	 * Set node as current
	 * @param NavigationNode $node
	 */
	public function setCurrentNode(NavigationNode $node)
	{
		if (isset($this->current)) {
			$this->current->isCurrent = false;
		}
		$node->isCurrent = true;
		$this->current = $node;
	}
	public function setCurrentByUrl()
	{
		if($this->getComponent("homepage")->url == $this->getPresenter()->link($this->getPresenter()->backlink()))
		{
			$this->setCurrentNode($this->getComponent("homepage"));
			return true;

		}
		$this->_currentNode($this->getComponent("homepage"));

	}
	private function _currentNode($node)
	{
		foreach($node->getComponents() as $c)
		{

			if($c->url == $this->getPresenter()->link($this->getPresenter()->backlink()))
				$this->setCurrentNode($c);
			else
				$this->_currentNode ($c);
		}
	}
	public function setTranslator($translator)
	{
		$this->translator = $translator;
	}
	/**
	 * Add navigation node as a child
	 * @param string $label
	 * @param string $url
	 * @return NavigationNode
	 */
	public function add($label, $url)
	{

		return $this->getComponent('homepage')->add($label, $url);
	}

	/**
	 * Setup homepage
	 * @param string $label
	 * @param string $url
	 * @return NavigationNode
	 */
	public function setupHomepage($label, $url)
	{
		$homepage = $this->getComponent('homepage');
		$homepage->label = $label;
		$homepage->url = $url;
		$this->useHomepage = true;
		return $homepage;
	}

	/**
	 * Homepage factory
	 * @param string $name
	 */
	protected function createComponentHomepage($name)
	{
		new NavigationNode($this, $name);
	}

	/**
	 * Render menu
	 * @param bool $renderChildren
	 * @param NavigationNode $base
	 * @param bool $renderHomepage
	 */
	public function renderMenu($renderChildren = TRUE, $base = NULL, $renderHomepage = TRUE)
	{
		$template = $this->createTemplate()
			->setFile($this->menuTemplate ?: __DIR__ . '/menu.latte');
		$template->homepage = $base ? $base : $this->getComponent('homepage');
		$template->useHomepage = $this->useHomepage && $renderHomepage;
		$template->renderChildren = $renderChildren;
		$template->children = $this->getComponent('homepage')->getComponents();
		if($this->translator)
			$template->setTranslator($this->translator);
		$template->render();
	}

	/**
	 * Render full menu
	 */
	public function render()
	{
		$this->renderMenu();
	}

	/**
	 * Render main menu
	 */
	public function renderMainMenu()
	{
		$this->renderMenu(FALSE);
	}

	/**
	 * Render breadcrumbs
	 */
	public function renderBreadcrumbs()
	{
		if (empty($this->current)) {
			return;
		}

		$items = array();
		$node = $this->current;

		while ($node instanceof NavigationNode) {
			$parent = $node->getParent();
			if (!$this->useHomepage && !($parent instanceof NavigationNode)) {
				break;
			}

			array_unshift($items, $node);
			$node = $parent;
		}

		$template = $this->createTemplate()
			->setFile($this->breadcrumbsTemplate ?: __DIR__ . '/breadcrumbs.latte');

		$template->items = $items;
		$template->render();
	}

	/**
	 * @param string $breadcrumbsTemplate
	 */
	public function setBreadcrumbsTemplate($breadcrumbsTemplate)
	{
		$this->breadcrumbsTemplate = $breadcrumbsTemplate;
	}

	/**
	 * @param string $menuTemplate
	 */
	public function setMenuTemplate($menuTemplate)
	{
		$this->menuTemplate = $menuTemplate;
	}

	/**
	 * @return \Navigation\NavigationNode
	 */
	public function getCurrentNode()
	{
		return $this->current;
	}

}
