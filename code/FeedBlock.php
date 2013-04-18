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
		'FeedURL'		=> 'TextField',
		'Results'		=> 'NumericField',
		'CacheTime'		=> 'NumericField',
		'Striptags'		=> 'CheckboxField',
		'Modifier'		=> 'DropdownField',
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

		$refresh = true;
		if(is_file($this->getCachePath())) {
			$refresh = false;
			$expires = !$refresh && $this->CacheTime > 0 ? time()-($this->CacheTime * 60) : 0;
			if($expires > filemtime($this->getCachePath())) {
				$refresh = true;
			}
		}
		if($refresh) {
			$this->refresh();
		}
		if(!is_file($this->getCachePath())) {
			return false;
		}

		$xml_source = file_get_contents($this->getCachePath());
		$xml = simplexml_load_string($xml_source);

		if(!count($xml)) {
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
			$date = new SS_Datetime('Date');
			$date->setValue((string) $item->pubDate);
			$title = new Text('Title');
			$title->setValue((string) $item->title);
			$desc = new Text('Description');
			$description = (string) $item->description;
			if($this->Striptags) {
				$description = strip_tags($description);
			}
			$desc->setValue($description);
			$link = (string) $item->link;
			// Create summary
			$summary = $description;
			$summary = strip_tags($summary);
			$summary = trim($summary);
			// Truncate summary line to 100 characters
			$max_len = 100;
			if(strlen($summary) > $max_len) {
				$summary = substr($summary, 0, $max_len) . '...';
			}
			// Add to result list
			$result->push(new ArrayData(array(
				'Title'			=> $title,
				'Date'			=> $date,
				'Link'			=> $link,
				'Description'	=> $desc,
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
	 * Get path to cache folder
	 *
	 * @return string
	 */
	function getCachePath() {
		if(empty($this->cachePath)) {
			$cacheFolderPath = realpath(__DIR__.'/..').'/cache';
			if(!file_exists($cacheFolderPath)) {
				Filesystem::makeFolder($cacheFolderPath);
			}
			if(!is_file($cacheFolderPath.'/.htaccess')) {
				file_put_contents($cacheFolderPath.'/.htaccess', "order deny, allow\ndeny from all", LOCK_EX);
			}
			$this->cachePath = $cacheFolderPath.'/'.md5($this->FeedURL).'.xml';
		}
		return $this->cachePath;
	}

	/**
	 * Refresh feed
	 */
	function refresh() {
		$xml = file_get_contents($this->FeedURL);
		if(empty($xml) || $xml === false) {
			// Try again with some context
			$opts = array(
				'http'=>array(
					'protocol_version'=>'1.1',
					'method'=>'GET',
					'header'=>array(
						'Connection: close'
					),
					'user_agent'=>$_SERVER['HTTP_USER_AGENT']
				)
			);
			$context  = stream_context_create($opts);
			$xml = file_get_contents($this->FeedURL, false, $context);
		}
		if(empty($xml) || $xml === false) {
			return false;
		}
		return (bool) file_put_contents($this->getCachePath(), $xml, LOCK_EX);
	}

}
