<?php
class NHP_Options_hw_divide extends NHP_Options{
	
	/**
	 * Field Constructor.
	 *
	 * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
	 *
	 * @since NHP_Options 1.0
	*/
	function __construct($field = array(), $value ='', $parent){
		
		parent::__construct($parent->sections, $parent->args, $parent->extra_tabs);
		$this->field = $field;
		$this->value = $value;
		//$this->render();
		
	}//function
	
	
	
	/**
	 * Field Render Function.
	 *
	 * Takes the vars and outputs the HTML for the field in the settings
	 *
	 * @since NHP_Options 1.0
	*/
	function render(){
		
		$class = (isset($this->field['class']))?' '.$this->field['class'].'':'';
		$title = isset($this->field['label'])? $this->field['label'] : ''; //title
		echo '</td></tr></table>';
        echo '<div class="hw-nhp-label '.$class.'"><strong>'.$title.'</strong></div>';
        echo '<table class="form-table no-border"><tbody><tr><th></th><td>';
		
	}//function
	
}//class
?>