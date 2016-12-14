<?php

namespace Ajax\semantic\html\collections\form;

use Ajax\semantic\html\collections\form\traits\TextFieldsTrait;
use Ajax\semantic\html\collections\form\traits\FieldTrait;
use Ajax\semantic\html\elements\HtmlInput;

class HtmlFormInput extends HtmlFormField {
	use TextFieldsTrait,FieldTrait;

	public function __construct($identifier, $label=NULL,$type="text",$value=NULL,$placeholder=NULL) {
		if(!isset($placeholder) && $type==="text")
			$placeholder=$label;
		parent::__construct("field-".$identifier, new HtmlInput($identifier,$type,$value,$placeholder), $label);
	}

	public function getDataField(){
		$field= $this->getField();
		if($field instanceof HtmlInput)
			$field=$field->getField();
		return $field;
	}
}