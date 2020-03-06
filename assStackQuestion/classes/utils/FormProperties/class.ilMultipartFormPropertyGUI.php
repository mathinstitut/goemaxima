<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

include_once("./Services/Form/classes/class.ilFormPropertyGUI.php");

/**
 * Multipart form property GUI class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilMultipartFormPropertyGUI extends ilFormPropertyGUI
{
	/**
	 * This array Includes all ilMultipartFormPart of this Form
	 * @var array
	 */
	private $parts = array();

	/**
	 * If true show property name
	 * @var bool
	 */
	private $show_title;

	/**
	 * Is the maximal width of the object
	 * @var int
	 */
	private $container_width;

	/**
	 * Includes the width for the part title, the part content and the footer
	 * This is for the bootstrap attribute col.
	 * @var array
	 */
	private $width_division = array();


	function __construct($a_title = "", $a_postvar = "", $a_container_width = 12, $a_show_title = TRUE)
	{
		parent::__construct($a_title, $a_postvar);

		//Maximum width of this object in boostrap columns
		$this->setContainerWidth($a_container_width);

		//Show title of the property or not
		$this->setShowTitle($a_show_title);

		//Depending on showing the title the width of each part will be different
		$this->determineWidthDivision();
	}

	/**
	 * Add a part to the form, setting the position value in the part object
	 * and in the parts array of this class.
	 * @param ilMultipartFormPart $part
	 */
	public function addPart(ilMultipartFormPart $part)
	{
		$this->parts[] = $part;
	}

	/**
	 * Insert property html
	 */
	function insert(&$a_tpl, $a_content_width = "")
	{
		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $this->render());
		if ($a_content_width) {
			$a_tpl->setVariable("CONTENT_WIDTH", $a_content_width);
		}
		$a_tpl->parseCurrentBlock();
	}

	/**
	 * Gets a standard width division for different parts
	 */
	public function determineWidthDivision()
	{
		if ($this->getShowTitle()) {
			$width_division = array(
				'title' => (int)floor($this->getContainerWidth() * 0.3),
				'content' => (int)floor($this->getContainerWidth() * 0.6),
				'footer' => (int)floor($this->getContainerWidth() * 0.1)
			);
		} else {
			$width_division = array(
				'title' => (int)floor($this->getContainerWidth() * 0.1),
				'content' => (int)floor($this->getContainerWidth() * 0.85),
				'footer' => (int)floor($this->getContainerWidth() * 0.1)
			);
		}

		$this->setWidthDivision($width_division);
	}

	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param array $parts
	 */
	public function setParts($parts)
	{
		$this->parts = $parts;
	}

	/**
	 * @return array
	 */
	public function getParts()
	{
		return $this->parts;
	}

	/**
	 * @param boolean $show_title
	 */
	public function setShowTitle($show_title)
	{
		$this->show_title = $show_title;
	}

	/**
	 * @return boolean
	 */
	public function getShowTitle()
	{
		return $this->show_title;
	}

	/**
	 * @param int $container_width
	 */
	public function setContainerWidth($container_width)
	{
		$this->container_width = $container_width;
	}

	/**
	 * @return int
	 */
	public function getContainerWidth()
	{
		return $this->container_width;
	}

	/**
	 * @param array $width_division
	 */
	public function setWidthDivision($width_division)
	{
		$this->width_division = $width_division;
	}

	/**
	 * @param $parameter
	 * @return mixed
	 * @throws Exception
	 */
	public function getWidthDivision($parameter)
	{
		if (isset($this->width_division[$parameter])) {
			return $this->width_division[$parameter];
		} else {
			throw new Exception('Parameter %s not valid for division', $parameter);
		}
	}



}