<?php
/**
 * Class FeedBlock
 *
 * Extends {@link Block} to facilitate links
 */
class LinkBlock extends Block
{
	
	/**
	 * Datafields
	 *
	 * @var array
	 */
	protected $_datafields = array(
		'CssClasses'	=> 'TextField',
		'Attributes'	=> 'TextareaField',
	);

	/**
	 * Summary fields for GridField
	 *
	 * @var array
	 */
	private static $summary_fields = array(
		'Thumbnail'		=> 'Image',
		'Title'			=> 'Title',
		'URL'			=> 'URL',
 	);

	/**
	 * Get CMS fields
	 *
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = new FieldList();

		$fields->push(new TextField('LinkTitle', _t('Block.LINKTITLE','Link title')));
		$fields->push(new TextField('LinkExternal', _t('Block.LINKEXTERNAL','External link URL')));

		if(class_exists('OptionalTreeDropdownField')) {
			// https://github.com/richardsjoqvist/silverstripe-optionaltreedropdownfield
			$treeField = new OptionalTreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL','Internal link'), 'SiteTree');
			$treeField->setEmptyString('No Page');
		}
		else {
			$treeField = new TreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL','Internal link'), 'SiteTree');
		}
		$fields->push($treeField);

		foreach($this->_datafields as $fieldname => $fieldclass) {
			$fields->push(new $fieldclass($fieldname, Block::getDataFieldLabel(__CLASS__, $fieldname)));
		}

		$imageField = new UploadField('Image', _t('Block.IMAGE','Image'));
		$imageField->getValidator()->setAllowedExtensions(array('jpg', 'gif', 'png'));
		$fields->push($imageField);

		//$fields->push(new NumericField('SortOrder', _t('Block.SORTORDER')));
		
		return $fields;
	}

	/**
	 * Get link url
	 *
	 * @return string
	 */
	function URL() {
		return parent::LinkURL();
	}
	
	/**
	 * Get link title
	 *
	 * @return string
	 */
	function Title() {
		return parent::LinkTitle();
	}
	
	/**
	 * Get link is external
	 *
	 * @return bool
	 */
	function IsExternal() {
		return parent::LinkIsExternal();
	}

	/**
	 * Get link is internal
	 *
	 * @return bool
	 */
	function IsInternal() {
		return !self::IsExternal();
	}
	
}