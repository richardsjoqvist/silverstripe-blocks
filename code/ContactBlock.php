<?php
/**
 * Class ContactBlock
 *
 * Extends {@link Block} to facilitate information for a contact
 */
class ContactBlock extends Block
{
	
	/**
	 * Data fields
	 *
	 * @var array
	 */
	protected $_datafields = array(
		'Role'			=> 'TextField',
		'Email'			=> 'EmailField',
		'Phone'			=> 'TextField',
		'Mobile'		=> 'TextField',
		'Street'		=> 'TextField',
		'Zip'			=> 'TextField',
		'City'			=> 'TextField',
		'Box'			=> 'TextField',
	);

	/**
	 * Summary field for GridField
	 *
	 * @var array
	 */
	public static $summary_fields = array(
		'Thumbnail'		=> 'Image',
		'Title'			=> 'Name',
		'Email'			=> 'Email',
		'Phone'			=> 'Phone',
		'Mobile'		=> 'Mobile',
		'Address'		=> 'Address',
 	);

	/**
	 * Get CMS fields
	 *
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = new FieldList();
		
		$fields->push(new TextField('Title', $this->getDataFieldLabel(__CLASS__, 'Name')));
		foreach($this->_datafields as $fieldname => $fieldclass) {
			$fields->push(new $fieldclass($fieldname, $this->getDataFieldLabel($this->ClassName, $fieldname)));
		}

		$imageField = new UploadField('Image', _t('Block.IMAGE','Image'));
		$imageField->getValidator()->setAllowedExtensions(array('jpg', 'gif', 'png'));
		$fields->push($imageField);

		//$fields->push(new NumericField('SortOrder', 'Sort Order'));
		
		return $fields;
	}
	
	/**
	 * Get contact name (alias)
	 *
	 * @return string
	 */
	function Name() {
		return $this->Title;
	}
	
	/**
	 * Get compiled address (for use with GridField primarily)
	 *
	 * @return string
	 */
	function Address() {
		$city = array();
		if(!empty($this->Zip)) {
			$city[] = $this->Zip;
		}
		if(!empty($this->City)) {
			$city[] = $this->City;
		}
		$address = array();
		if(!empty($this->Street)) {
			$address[] = $this->Street;
		}
		$address[] = join(' ',$city);
		if(!empty($this->Box)) {
			$address[] = $this->Box;
		}
		return join(", ", $address);
	}
	
}