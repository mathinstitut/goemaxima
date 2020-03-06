<?php
/**
 * Copyright (c) 2014 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */


/**
 * Multipart form part object class
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 * @author Jesus Copado <jesus.copado@ili.fau.de>
 *
 */
class ilMultipartFormPart
{

	/**
	 * Title of the part.
	 * @var string
	 */
	private $title;

	/**
	 * Array of form properties objects included in this part.
	 * @var array
	 */
	private $content = array();

	/**
	 * type of the part
	 * @var string
	 */
	private $type;

	/**
	 * OBJECT CONSTRUCTOR
	 * @param $a_title string the title of this part
	 */
	function __construct($a_title, $a_postvar = "")
	{
		$this->setTitle($a_title);
	}

	/**
	 * Add a form property to the end of the list of content
	 * @param $a_form_property
	 */
	public function addFormProperty($a_form_property)
	{
		$this->content[] = $a_form_property;
	}


	/*
	 * GETTERS AND SETTERS
	 */

	/**
	 * @param string $a_title
	 */
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param array $a_content
	 */
	public function setContent($a_content)
	{
		$this->content = $a_content;
	}

	/**
	 * @return array
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

}