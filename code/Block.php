<?php
/**
 * Class Block
 *
 * Basic block class. This class should be extended for usage in the cms.
 */
class Block extends DataObject {
 	
	/**
	 * Fields
	 *
	 * @var array
	 */
	private static $db = array(
		'Title'				=> 'Text',
		'LeadIn'			=> 'HTMLText',
		'Content'			=> 'HTMLText',
		'LinkTitle'			=> 'Text',
		'LinkExternal'		=> 'Text',
		'SortOrder'			=> 'Int',
		'Data'				=> 'Text',
	);

	/**
	 * Relationships
	 *
	 * @var array
	 */
	private static $has_one = array(
		'Page'				=> 'Page',
		'Image'				=> 'Image',
		'LinkInternal'		=> 'SiteTree',
	);


	/**
	 * Summary fields for GridField
	 *
	 * @var array
	 */
	private static $summary_fields = array(
		'Thumbnail'			=> 'Image',
		'Title'				=> 'Title',
		'LeadInText'		=> 'Lead in',
		'ContentText'		=> 'Content',
 	);

	/**
	 * Placeholder for additional data fields for extending blocks
	 * @var null
	 */
	protected $_datafields = null;

	/**
	 * Default sort order
	 *
	 * @var string
	 */
	private static $default_sort = "Block.SortOrder ASC";

	/**
	 * Constructor
	 *
	 * @param array $record
	 * @param bool $isSingleton
	 */
	function __construct($record = null, $isSingleton = false) {
		if($record['ID'] > 0) {
			// Extract data from Data
			if(!empty($record['Data'])) {
				$data = unserialize($record['Data']);
				foreach($data as $fieldname => $value) {
					$record[$fieldname] = $value;
				}
			}
		}
		parent::__construct($record, $isSingleton);
	}
	
	/**
	 * Get CMS fields
	 *
	 * @return FieldList
	 */
	public function getCMSFields() {
		$fields = new FieldList();
		
		$fields->push(new TextField('Title', _t('Block.TITLE','Title')));

		$leadInField = new HTMLEditorField('LeadIn', _t('Block.LEADIN','Lead In'));
		$leadInField->setRows(2);
		$fields->push($leadInField);

		$contentField = new HTMLEditorField('Content', _t('Block.CONTENT','Content'));
		$contentField->setRows(6);
		$fields->push($contentField);

		$imageField = new UploadField('Image', _t('Block.IMAGE','Image'));
		$imageField->getValidator()->setAllowedExtensions(array('jpg', 'gif', 'png'));
		$fields->push($imageField);
		
		$fields->push(new TextField('LinkTitle', _t('Block.LINKTITLE','Link title')));
		$fields->push(new TextField('LinkExternal', _t('Block.LINKEXTERNAL','External link URL')));

		if(class_exists('OptionalTreeDropdownField')) {
			// https://github.com/richardsjoqvist/silverstripe-optionaltreedropdownfield
			$treeField = new OptionalTreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL','Internal link'), 'SiteTree');
			$treeField->setEmptyString('(Choose)');
		}
		else {
			$treeField = new TreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL','Internal link'), 'SiteTree');
		}
		$fields->push($treeField);

		//$fields->push(new NumericField('SortOrder', _t('Block.SORTORDER','Sort order)));
		
		return $fields;
	}

	/**
	 * Text-only Lead In
	 *
	 * @return string
	 */
	public function LeadInText() {
		if(!$this->LeadIn) {
			return '';
		}
		$str = $this->LeadIn;
		$str = html_entity_decode($str);
		$str = strip_tags($str);
		$str = trim($str);
		return $str;
	}

	/**
	 * Text-only Content
	 *
	 * @return string
	 */
	public function ContentText() {
		if(!$this->Content) {
			return '';
		}
		$str = $this->Content;
		$str = html_entity_decode($str);
		$str = strip_tags($str);
		$str = trim($str);
		return $str;
	}

	/**
	 * Get thumbnail for list in admin
	 *
	 * @return unknown
	 */
	function Thumbnail() {
		if($Image = $this->Image()) {
			return $Image->CMSThumbnail();
		}
		return null;
	}
	
	/**
	 * Check if this block has a valid link
	 *
	 * @return bool
	 */
	public function HasLink() {
		$url = trim($this->LinkExternal);
		return $this->LinkInternalID > 0 || !empty($url);
	}
	
	/**
	 * Check if link is external
	 *
	 * @return bool
	 */
	public function LinkIsExternal() {
		$url = trim($this->LinkExternal);
		return !empty($url);
	}
	
	/**
	 * Get link url
	 *
	 * @return string
	 */
	public function LinkURL() {
		$url = trim($this->LinkExternal);
		if($this->HasLink() && empty($url)) {
			$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			if($r = DataObject::get_one('SiteTree',"{$bt}SiteTree{$bt}.{$bt}ID{$bt} = '{$this->LinkInternalID}'")) {
				$url = $r->RelativeLink();
			}
		}
		return $url;
	}
	
	/**
	 * Get link title
	 *
	 * @return string
	 */
	public function LinkTitle() {
		$title = trim($this->LinkTitle);
		if(empty($title)) {
			if(!$this->LinkIsExternal()) {
				// Internal link
				$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
				if($r = DataObject::get_one('SiteTree',"{$bt}SiteTree{$bt}.{$bt}ID{$bt} = '{$this->LinkInternalID}'")) {
					$title = $r->Title;
				}
			} else {
				// Internal link
				$title = trim($this->Title);
			}
		}
		return $title;
	}
	
	/**
	 * Get compiled link
	 * 
	 * @return string
	 */
	public function Link() {
		if($this->HasLink()) {
			$url	= $this->LinkURL();
			$class	= $this->LinkIsExternal() ? ' class="external"' : '';
			$title	= $this->LinkTitle();
			return "<a href=\"{$url}\"{$class}>{$title}</a>"; 
		}
		return '';
	}

	/**
	 * Get link details
	 * 
	 * @return string
	 */
	public function LinkDetails() {
		if($this->HasLink()) {
			$url	= $this->LinkURL();
			$title	= $this->LinkTitle();
			return "{$title}: {$url}"; 
		}
		return '';
	}

	/**
	 * Get data field label
	 *
	 * @param string $classname
	 * @param string $fieldname
	 * @return string
	 */
	function getDataFieldLabel($classname, $fieldname) {
		// Try to translate fieldname
		$label = _t($classname . '.' . strtoupper($fieldname));
		if(empty($label)) {
			$label = _t('Block.' . strtoupper($fieldname));
		}
		if(empty($label)) {
			// Use fieldname as title
			$label = preg_replace("/([A-Z])/sU", " $1", $fieldname);
			$label = str_replace(' U R L',' URL',$label);
			$label = trim($label);
			if(!strpos($label, ' ')) {
				$label = ucfirst($fieldname);
			}
		}
		return $label;
	}

	/**
	 * Compile datafields and save them in Data as a serialized array
	 */
	function onBeforeWrite() {
		if($this->_datafields) {
			$data = array();
			$record = $this->record;
			foreach ($this->_datafields as $fieldname => $fieldtype) {
				if(isset($record[$fieldname])) {
					$data[$fieldname] = $record[$fieldname];
				}
			}
			$this->Data = $data ? serialize($data) : '';
		}
		parent::onBeforeWrite();
	}

}
