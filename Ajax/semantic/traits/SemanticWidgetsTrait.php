<?php
namespace Ajax\semantic\traits;

use Ajax\semantic\widgets\datatable\DataTable;
use Ajax\semantic\widgets\dataelement\DataElement;
use Ajax\semantic\widgets\dataform\DataForm;

trait SemanticWidgetsTrait {

	abstract public function addHtmlComponent($htmlComponent);

	/**
	 * @param string $identifier
	 * @param string $model
	 * @param array $instances
	 * @return DataTable
	 */
	public function dataTable($identifier,$model, $instances){
		return $this->addHtmlComponent(new DataTable($identifier,$model,$instances));
	}

	/**
	 * @param string $identifier
	 * @param object $instance
	 * @return DataElement
	 */
	public function dataElement($identifier, $instance){
		return $this->addHtmlComponent(new DataElement($identifier,$instance));
	}

	/**
	 * @param string $identifier
	 * @param object $instance
	 * @return DataForm
	 */
	public function dataForm($identifier, $instance){
		return $this->addHtmlComponent(new DataForm($identifier,$instance));
	}
}