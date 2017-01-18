<?php

namespace Ajax\semantic\widgets\dataelement;

use Ajax\common\Widget;
use Ajax\semantic\widgets\base\InstanceViewer;
use Ajax\semantic\widgets\datatable\PositionInTable;
use Ajax\semantic\html\collections\HtmlTable;
use Ajax\JsUtils;
use Ajax\service\JArray;

/**
 * DataElement widget for displaying an instance of model
 * @version 1.0
 * @author jc
 * @since 2.2
 *
 */
class DataElement extends Widget {

	public function __construct($identifier, $modelInstance=NULL) {
		parent::__construct($identifier, null,$modelInstance);
		$this->_instanceViewer=new InstanceViewer();
		$this->content=["table"=>new HtmlTable($identifier, 0,2)];
		$this->content["table"]->setDefinition();
		$this->_toolbarPosition=PositionInTable::BEFORETABLE;
	}

	public function compile(JsUtils $js=NULL,&$view=NULL){
		$this->_instanceViewer->setInstance($this->_modelInstance);

		$table=$this->content["table"];
		$this->_generateContent($table);

		if(isset($this->_toolbar)){
			$this->_setToolbarPosition($table);
		}
		$this->content=JArray::sortAssociative($this->content, [PositionInTable::BEFORETABLE,"table",PositionInTable::AFTERTABLE]);
		return parent::compile($js,$view);
	}

	/**
	 * @param HtmlTable $table
	 */
	protected function _generateContent($table){
		$captions=$this->_instanceViewer->getCaptions();
		$values= $this->_instanceViewer->getValues();
		$count=$this->_instanceViewer->count();
		for($i=0;$i<$count;$i++){
			$table->addRow([$captions[$i],$values[$i]]);
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \Ajax\common\Widget::getHtmlComponent()
	 * @return HtmlTable
	 */
	public function getHtmlComponent() {
		return $this->content["table"];
	}

	/**
	 * {@inheritdoc}
	 * @see \Ajax\common\Widget::_setToolbarPosition()
	 */
	protected function _setToolbarPosition($table, $captions=NULL) {
		$this->content[$this->_toolbarPosition]=$this->_toolbar;
	}

	/**
	 * The callback function called after the insertion of each row when fromDatabaseObjects is called
	 * callback function takes the parameters $row : the row inserted and $object: the instance of model used
	 * @param callable $callback
	 * @return DataElement
	 */
	public function onNewRow($callback) {
		$this->content["table"]->onNewRow($callback);
		return $this;
	}
}