<?php
/**
 * Class FeedBlock
 *
 * Extends {@link Block} to facilitate RSS feeds
 */
class FeedBlock extends Block
{

	/**
	 * Path to cache folder
	 *
	 * @var string
	 */
	protected $cachePath = '';

	/**
	 * Datafields
	 *
	 * @var array
	 */
	protected $_datafields = array(
		'FeedURL'			=> 'TextField',
		'Results'			=> 'NumericField',
		'SummaryMaxLength'	=> 'NumericField',
		'CacheTime'			=> 'NumericField',
		'Striptags'			=> 'CheckboxField',
		'Modifier'			=> 'DropdownField',
	);

	/**
	 * Summary fields for GridField
	 *
	 * @var array
	 */
	public static $summary_fields = array(
		'Thumbnail'		=> 'Image',
		'Title'			=> 'Title',
		'FeedURL'		=> 'URL',
		'ItemsInFeed'	=> 'Items in feed',
	);

	/**
	 * Get CMS fields
	 *
	 * @return FieldList
	 */
	function getCMSFields() {
		$fields = new FieldList();
		
		$fields->push(new TextField('Title', _t('Block.TITLE','Title')));

		$imageField = new UploadField('Image', _t('Block.IMAGE','Image'));
		$imageField->getValidator()->setAllowedExtensions(array('jpg', 'gif', 'png'));
		$fields->push($imageField);

		foreach($this->_datafields as $fieldname => $fieldclass) {
			if($fieldname != 'Modifier') { // Add field by hand later
				$fields->push(new $fieldclass($fieldname, Block::getDataFieldLabel(__CLASS__, $fieldname)));
			}
		}
		// Add modifier field (select function to run feed item through before displaying it)
		if(isset($this->modifier_functions)){
			$choises = array(
				''	=> 'None'
			);
			foreach($this->modifier_functions as $f) {
				$choises[$f] = $f;
			}
			$fields->push(new DropdownField('Modifier', _t('FeedBlock.MODIFIER','Feed item filter'), $choises));
		}
		
		$fields->push(new TextField('LinkExternal', _t('FeedBlock.LINKEXTERNAL','External link URL')));

		if(class_exists('OptionalTreeDropdownField')) {
			// https://github.com/richardsjoqvist/silverstripe-optionaltreedropdownfield
			$treeField = new OptionalTreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL','Internal link'), 'SiteTree');
			$treeField->setEmptyString('(Choose)');
		}
		else {
			$treeField = new TreeDropdownField('LinkInternalID', _t('Block.LINKINTERNAL','Internal link'), 'SiteTree');
		}
		$fields->push($treeField);

		//$fields->push(new NumericField('SortOrder', _t('Block.SORTORDER')));
		
		return $fields;
	}

	/**
	 * Get block link url
	 *
	 * @return string
	 */
	function URL() {
		return parent::LinkURL();
	}
	
	/**
	 * Get block link is external
	 *
	 * @return bool
	 */
	function IsExternal() {
		return parent::LinkIsExternal();
	}

	/**
	 * Get block link is internal
	 *
	 * @return bool
	 */
	function IsInternal() {
		return !self::IsExternal();
	}
	
	/**
	 * Load feed xml and return items
	 * 
	 * @param boolean $refresh
	 * @return ArrayList or boolean false
	 */
	function Items($refresh=false) {
		if(!$this->FeedURL) {
			return false;
		}

		if(!$xml = $this->loadXml()) {
			return false;
		}

		$result = new ArrayList;
		$counter = (int) $this->Results;
		if(!$counter) {
			// Return all posts
			$counter = -1;
		}

		foreach($xml->channel->item as $item)
		{
			// Date
			$date = new SS_Datetime('Date');
			$date->setValue((string) $item->pubDate);
			// Title
			$itemTitle = (string) $item->title;
			$itemTitle = html_entity_decode($itemTitle);
			$title = new Text('Title');
			$title->setValue($itemTitle);
			// Description
			$itemDescription = (string) $item->description;
			$itemDescription = html_entity_decode($itemDescription);
			$description = new Text('Description');
			if($this->Striptags) {
				$itemDescription = strip_tags($itemDescription);
			}
			$description->setValue($itemDescription);
			// Link
			$link = (string) $item->link;
			// Summary
			$summary = $itemDescription;
			$summary = strip_tags($summary);
			$summary = trim($summary);
			// Get first paragraph
			$summary = explode("\n",$summary);
			$summary = array_shift($summary);
			// Truncate summary if necessary
			$maxLength = (int) $this->SummaryMaxLength;
			if($maxLength && strlen($summary) > $maxLength) {
				$summary = substr($summary, 0, $maxLength) . '...';
			}
			// Add to result list
			$result->push(new ArrayData(array(
				'Title'			=> $title,
				'Date'			=> $date,
				'Link'			=> $link,
				'Description'	=> $description,
				'Summary'		=> $summary,
			)));
			if($counter) {
				$counter--;
				if(!$counter) break;
			}
		}
		// Apply custom modifier function to each entry
		if(
			count($result)
			&& !empty($this->Modifier)
			&& $this->ClassName != __CLASS__
			&& method_exists($this->ClassName,$this->Modifier)
		) {
			try {
				$m = $this->Modifier;
				foreach($result as $item) {
					$this->$m($item);
				}
			}
			catch (Exception $e) {
				// Ignore
			}
		}
		// Return items
		return $result;
	}
	
	/**
	 * Get number of items in feed
	 *
	 * @return string
	 */
	function ItemsInFeed() {
		return count($this->Items());
	}

	/**
	 * Load RSS Feed
	 */
	private function loadXml($refresh=false) {
		if(empty($this->FeedURL)) {
			return false;
		}
		$cacheKey = md5($this->FeedURL);
		$xml = null;
		// Get the Zend Cache to load/store cache into
		$cache = SS_Cache::factory('FeedBlock_xml_', 'Output', array(
			'automatic_serialization' => false,
			'lifetime' => null
		));
		// Unless force refreshing, try loading from cache
		if (!$refresh) {
			// The PHP config sources are always needed
			if($xml = $cache->load($cacheKey)) {
				return simplexml_load_string($xml);
			}
		}
		// Load feed and cache it
		$xml = file_get_contents($this->FeedURL);
		if(!empty($xml)) {
			try {
				$xmlObj = @simplexml_load_string($xml);
			}
			catch(Exception $e) {
				return false;
			}
			if(isset($xmlObj->channel)) {
				$cache->save($xml, $cacheKey);
				return $xmlObj;
			}
		}
		return false;
	}

	/**
	 * Refresh feed
	 */
	function refresh() {
		if($xml = $this->loadXml(true)) {
			return get_class($xml) === 'SimpleXMLElement';
		}
		return false;
	}

}
