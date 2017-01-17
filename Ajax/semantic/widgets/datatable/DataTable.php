<?php

namespace Ajax\semantic\widgets\datatable;

use Ajax\common\Widget;
use Ajax\JsUtils;
use Ajax\semantic\html\collections\HtmlTable;
use Ajax\semantic\html\elements\HtmlInput;
use Ajax\semantic\html\collections\menus\HtmlPaginationMenu;
use Ajax\semantic\html\modules\checkbox\HtmlCheckbox;
use Ajax\semantic\html\elements\HtmlButton;
use Ajax\semantic\html\collections\menus\HtmlMenu;
use Ajax\semantic\html\base\constants\Direction;
use Ajax\service\JArray;
use Ajax\semantic\widgets\base\FieldAsTrait;

/**
 * DataTable widget for displaying list of objects
 * @author jc
 *
 */
class DataTable extends Widget {
	use FieldAsTrait;

	protected $_searchField;
	protected $_urls;
	protected $_pagination;
	protected $_hasCheckboxes;
	protected $_toolbar;
	protected $_compileParts;
	protected $_toolbarPosition;

	public function run(JsUtils $js){
		if($this->_hasCheckboxes && isset($js)){
			$js->execOn("change", "#".$this->identifier." [name='selection[]']", "
		var \$parentCheckbox=\$('#ck-main-ck-{$this->identifier}'),\$checkbox=\$('#{$this->identifier} [name=\"selection[]\"]'),allChecked=true,allUnchecked=true;
		\$checkbox.each(function() {if($(this).prop('checked')){allUnchecked = false;}else{allChecked = false;}});
		if(allChecked) {\$parentCheckbox.checkbox('set checked');}else if(allUnchecked){\$parentCheckbox.checkbox('set unchecked');}else{\$parentCheckbox.checkbox('set indeterminate');}");
		}
		parent::run($js);
	}

	public function __construct($identifier,$model,$modelInstance=NULL) {
		parent::__construct($identifier, $model,$modelInstance);
		$this->_instanceViewer=new InstanceViewer();
		$this->content=["table"=>new HtmlTable($identifier, 0,0)];
		$this->_toolbarPosition=PositionInTable::BEFORETABLE;
	}

	public function compile(JsUtils $js=NULL,&$view=NULL){
		$this->_instanceViewer->setInstance($this->_model);
		$captions=$this->_instanceViewer->getCaptions();

		$table=$this->content["table"];

		if($this->_hasCheckboxes){
			$ck=new HtmlCheckbox("main-ck-".$this->identifier,"");
			$ck->setOnChecked("$('#".$this->identifier." [name=%quote%selection[]%quote%]').prop('checked',true);");
			$ck->setOnUnchecked("$('#".$this->identifier." [name=%quote%selection[]%quote%]').prop('checked',false);");
			\array_unshift($captions, $ck);
		}

		$table->setRowCount(0, \sizeof($captions));
		$table->setHeaderValues($captions);
		if(isset($this->_compileParts))
			$table->setCompileParts($this->_compileParts);
		if(isset($this->_searchField)){
			if(isset($js))
				$this->_searchField->postOn("change", $this->_urls,"{'s':$(this).val()}","-#".$this->identifier." tbody",["preventDefault"=>false]);
		}

		$this->_generateContent($table);

		if($this->_hasCheckboxes){
			if($table->hasPart("thead"))
				$table->getHeader()->getCell(0, 0)->addToProperty("class","no-sort");
		}

		if(isset($this->_pagination) && $this->_pagination->getVisible()){
			$this->_generatePagination($table);
		}
		if(isset($this->_toolbar)){
			$this->_setToolbarPosition($table, $captions);
		}
		$this->content=JArray::sortAssociative($this->content, [PositionInTable::BEFORETABLE,"table",PositionInTable::AFTERTABLE]);
		return parent::compile($js,$view);
	}

	private function _generateContent($table){
		$objects=$this->_modelInstance;
		if(isset($this->_pagination)){
			$objects=$this->_pagination->getObjects($this->_modelInstance);
		}
		InstanceViewer::setIndex(0);
		$table->fromDatabaseObjects($objects, function($instance){
			$this->_instanceViewer->setInstance($instance);
			$result= $this->_instanceViewer->getValues();
			if($this->_hasCheckboxes){
				$ck=new HtmlCheckbox("ck-".$this->identifier,"");
				$field=$ck->getField();
				$field->setProperty("value",$this->_instanceViewer->getIdentifier());
				$field->setProperty("name", "selection[]");
				\array_unshift($result, $ck);
			}
			return $result;
		});
	}

	private function _generatePagination($table){
		$footer=$table->getFooter();
		$footer->mergeCol();
		$menu=new HtmlPaginationMenu("pagination-".$this->identifier,$this->_pagination->getPagesNumbers());
		$menu->floatRight();
		$menu->setActiveItem($this->_pagination->getPage()-1);
		$footer->setValues($menu);
		$menu->postOnClick($this->_urls,"{'p':$(this).attr('data-page')}","-#".$this->identifier." tbody",["preventDefault"=>false]);
	}

	private function _setToolbarPosition($table,$captions){
		switch ($this->_toolbarPosition){
			case PositionInTable::BEFORETABLE:case PositionInTable::AFTERTABLE:
				if(isset($this->_compileParts)===false){
					$this->content[$this->_toolbarPosition]=$this->_toolbar;
				}
				break;
			case PositionInTable::HEADER:case PositionInTable::FOOTER: case PositionInTable::BODY:
				$this->addToolbarRow($this->_toolbarPosition,$table, $captions);
				break;
		}
	}

	/**
	 * Associates a $callback function after the compilation of the field at $index position
	 * The $callback function can take the following arguments : $field=>the compiled field, $index: the field position, $instance : the active instance of the object
	 * @param int $index postion of the compiled field
	 * @param callable $callback function called after the field compilation
	 * @return \Ajax\semantic\widgets\datatable\DataTable
	 */
	public function afterCompile($index,$callback){
		$this->_instanceViewer->afterCompile($index,$callback);
		return $this;
	}

	private function addToolbarRow($part,$table,$captions){
		$row=$table->getPart($part)->addRow(\sizeof($captions));
		$row->mergeCol();
		$row->setValues([$this->_toolbar]);
	}

	public function getInstanceViewer() {
		return $this->_instanceViewer;
	}

	public function setInstanceViewer($_instanceViewer) {
		$this->_instanceViewer=$_instanceViewer;
		return $this;
	}

	public function setCaptions($captions){
		$this->_instanceViewer->setCaptions($captions);
		return $this;
	}

	public function setFields($fields){
		$this->_instanceViewer->setVisibleProperties($fields);
		return $this;
	}

	public function addField($field){
		$this->_instanceViewer->addField($field);
		return $this;
	}

	public function insertField($index,$field){
		$this->_instanceViewer->insertField($index, $field);
		return $this;
	}

	public function insertInField($index,$field){
		$this->_instanceViewer->insertInField($index, $field);
		return $this;
	}

	public function setValueFunction($index,$callback){
		$this->_instanceViewer->setValueFunction($index, $callback);
		return $this;
	}

	public function setIdentifierFunction($callback){
		$this->_instanceViewer->setIdentifierFunction($callback);
		return $this;
	}

	public function getHtmlComponent(){
		return $this->content["table"];
	}

	public function getUrls() {
		return $this->_urls;
	}

	public function setUrls($urls) {
		$this->_urls=$urls;
		return $this;
	}

	public function paginate($items_per_page=10,$page=1){
		$this->_pagination=new Pagination($items_per_page,4,$page);
	}

	public function getHasCheckboxes() {
		return $this->_hasCheckboxes;
	}

	public function setHasCheckboxes($_hasCheckboxes) {
		$this->_hasCheckboxes=$_hasCheckboxes;
		return $this;
	}

	public function refresh($compileParts=["tbody"]){
		$this->_compileParts=$compileParts;
		return $this;
	}
	/**
	 * @param string $caption
	 * @param callable $callback
	 * @return callable
	 */
	private function getFieldButtonCallable($caption,$callback=null){
		return $this->getCallable($this->getFieldButton($caption),$callback);
	}

	/**
	 * @param mixed $object
	 * @param callable $callback
	 * @return callable
	 */
	private function getCallable($object,$callback=null){
		$result=function($instance) use($object,$callback){
			if(isset($callback)){
				if(\is_callable($callback)){
					$callback($object,$instance);
				}
			}
			return $object;
		};
		return $result;
	}

	/**
	 * @param string $caption
	 * @return HtmlButton
	 */
	private function getFieldButton($caption){
			$bt=new HtmlButton("",$caption);
			$bt->setProperty("data-ajax",$this->_instanceViewer->getIdentifier());
			return $bt;
	}

	/**
	 * Inserts a new Button for each row
	 * @param string $caption
	 * @param callable $callback
	 * @return \Ajax\semantic\widgets\datatable\DataTable
	 */
	public function addFieldButton($caption,$callback=null){
		$this->addField($this->getFieldButtonCallable($caption,$callback));
		return $this;
	}

	/**
	 * Inserts a new Button for each row at col $index
	 * @param int $index
	 * @param string $caption
	 * @param callable $callback
	 * @return \Ajax\semantic\widgets\datatable\DataTable
	 */
	public function insertFieldButton($index,$caption,$callback=null){
		$this->insertField($index, $this->getFieldButtonCallable($caption,$callback));
		return $this;
	}

	/**
	 * Inserts a new Button for each row in col at $index
	 * @param int $index
	 * @param string $caption
	 * @param callable $callback
	 * @return \Ajax\semantic\widgets\datatable\DataTable
	 */
	public function insertInFieldButton($index,$caption,$callback=null){
		$this->insertInField($index, $this->getFieldButtonCallable($caption,$callback));
		return $this;
	}

	private function addDefaultButton($icon,$class=null,$callback=null){
		$bt=$this->getDefaultButton($icon,$class);
		$this->addField($this->getCallable($bt,$callback));
		return $this;
	}

	private function insertDefaultButtonIn($index,$icon,$class=null,$callback=null){
		$bt=$this->getDefaultButton($icon,$class);
		$this->insertInField($index,$this->getCallable($bt,$callback));
		return $this;
	}

	private function getDefaultButton($icon,$class=null){
		$bt=$this->getFieldButton("");
		$bt->asIcon($icon);
		if(isset($class))
			$bt->addToProperty("class", $class);
		return $bt;
	}

	public function addDeleteButton($callback=null){
		return $this->addDefaultButton("remove","delete red basic",$callback);
	}

	public function addEditButton($callback=null){
		return $this->addDefaultButton("edit","edit basic",$callback);
	}

	public function addEditDeleteButtons($callbackEdit=null,$callbackDelete=null){
		$this->addEditButton($callbackEdit);
		$index=$this->_instanceViewer->visiblePropertiesCount()-1;
		$this->insertDeleteButtonIn($index,$callbackDelete);
		return $this;
	}

	public function insertDeleteButtonIn($index,$callback=null){
		return $this->insertDefaultButtonIn($index,"remove","delete red basic",$callback);
	}

	public function insertEditButtonIn($index,$callback=null){
		return $this->insertDefaultButtonIn($index,"edit","edit basic",$callback);
	}

	public function setSelectable(){
		$this->content["table"]->setSelectable();
		return $this;
	}

	/**
	 * @return \Ajax\semantic\html\collections\menus\HtmlMenu
	 */
	public function getToolbar(){
		if(isset($this->_toolbar)===false){
			$this->_toolbar=new HtmlMenu("toolbar-".$this->identifier);
			$this->_toolbar->setSecondary();
		}
		return $this->_toolbar;
	}

	/**
	 * @param unknown $element
	 * @return \Ajax\common\html\HtmlDoubleElement
	 */
	public function addInToolbar($element){
		$tb=$this->getToolbar();
		return $tb->addItem($element);
	}

	public function addItemInToolbar($caption,$icon=NULL){
		$result=$this->addInToolbar($caption);
		$result->addIcon($icon);
		return $result;
	}

	public function addButtonInToolbar($caption){
		$bt=new HtmlButton("",$caption);
		return $this->addInToolbar($bt);
	}

	public function addLabelledIconButtonInToolbar($caption,$icon,$before=true,$labeled=false){
		$bt=new HtmlButton("",$caption);
		$bt->addIcon($icon,$before,$labeled);
		return $this->addInToolbar($bt);
	}


	public function addSearchInToolbar(){
		return $this->addInToolbar($this->getSearchField())->setPosition("right");
	}

	public function getSearchField(){
		if(isset($this->_searchField)===false){
			$this->_searchField=new HtmlInput("search-".$this->identifier,"search","","Search...");
			$this->_searchField->addIcon("search",Direction::RIGHT);
		}
		return $this->_searchField;
	}

	public function setSortable($colIndex=NULL) {
		$this->content["table"]->setSortable($colIndex);
		return $this;
	}

	protected function _getFieldIdentifier($prefix){
		return $this->identifier."-{$prefix}-".$this->_instanceViewer->getIdentifier();
	}
}