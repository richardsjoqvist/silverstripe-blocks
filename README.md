## Blocks SilverStripe module

Blocks is a generic class which can be used to build small content blocks which can be associated globally or with a specific page.
Compatible with SilverStripe 3 (tested with 3.0.5)

## Installation

Drop the module into your SilverStripe project and run /dev/build

## Optional dependencies

Blocks uses the [OptionalTreeDropdownField](https://github.com/richardsjoqvist/silverstripe-optionaltreedropdownfield)
plugin if it's installed in your project.

Blocks also uses the [SortableGridField](https://github.com/UndefinedOffset/SortableGridField) plugin if it's installed
in your project.

## Usage

### Implementation

Blocks needs to be extended to work well. Create a php-file in /mysite/code and call it something like
"ContentBlock.php". Add the following code to the file:

	class ContentBlock extends Block {
	}

Next you need to set up a relationship for the newly created class, for example in Page.php:

	public static $has_many = array(
		'ContentBlocks'	=> 'ContentBlock',
	);

Run /dev/build to register the new class and relationship.
Finally add a manager (pre-defined GridField) for the ContentBlock class in getCmsFields():

	class Page extends SiteTree
	{
		function getCMSFields()
		{
			$fields = parent::getCMSFields();
			//                                 Tab              Manager     Source Class        Title             DataList
			//                                  |                  |             |                |                   |
			$fields->addFieldToTab("Root.ContentBlocks", new Block_Manager('ContentBlock', 'Content Blocks', $this->ContentBlocks()) );
			return $fields;
		}
	}

Repeat the steps above to create another class, like "BannerBlock" to create another type of block. There is no limit on
how many types of blocks a site can have.

### Customizing extended blocks

Extended blocks can be customized just like any other type of DataObject exteded class, for example:

	class ContentBlock extends Block
	{
		public function getCMSFields()
		{
			$fields = parent::getCMSFields();
			$fields->removeByName('Content');
			return $fields;
		}
	}

The one exception to the rule is how to add additional data fields to an extended block. Usually additional fields are
added to the static $db variable, but since custom blocks should extends the base Block class, extra fields must be
added to the protected $_datafields variable, and are added as form field types instead of data types.
See [Form Field Types documentation](http://doc.silverstripe.org/framework/en/reference/form-field-types) for more information.
Example:

	class ContentBlock extends Block
	{
		/**
		 * Extra Datafields
		 * @var array
		 */
		protected $_datafields = array(
			'SomeText'		=> 'TextField',
			'MoreContent'	=> 'HTMLEditorField',
			'MyNumber'		=> 'NumericField',
			'CheckThis'		=> 'CheckboxField',
			'DropThat'		=> 'DropdownField',
		);
	}

### Templating <a id="templating"></a>

Blocks connected to a page can be displayed in a template using a loop:

	<% loop ContentBlocks %>
		$Title
		$LeadIn
		$Content
		$Image.SetWidth(150)
		<% if HasLink %>
			<a href="$LinkURL" <% if LinkIsExternal %>class="external"<% end_if%>>$LinkTitle</a>
		<% end_if %>
	<% end_loop %>

### Inheritance

You may want to create global blocks or inherit blocks from other pages. In such case you can create a function in the
Page_Controller class that allows more granular control over which ContentBlocks are returned to the loop:

	class Page_Controller extends ContentController
	{
		function ContentBlocks($limit='')
		{
			$id = $this->dataRecord->ID;
			$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			if($r = DataObject::get("Block","{$bt}Block{$bt}.{$bt}ClassName{$bt} = 'ContentBlock' AND {$bt}Block{$bt}.{$bt}PageID{$bt} = '{$id}'","","",$limit)) {
				return $r;
			}
			if(class_exists("HomePage")) {
				if($HomePage = Translatable::get_one_by_locale('HomePage', Translatable::get_current_locale())) {
					$id = $HomePage->ID;
					return DataObject::get("Block","{$bt}Block{$bt}.{$bt}ClassName{$bt} = 'ContentBlock' AND {$bt}Block{$bt}.{$bt}PageID{$bt} = '{$id}'","","",$limit);
				}
			}
			return null;
		}
	}

This function returns the ContentBlocks for the current page, but if the current page does not have any ContentBlocks it
moves on to the HomePage and returns any ContentBlocks from there instead.

## Included pre-defined models

The module includes a few models which can be used out of the box (or exteded) for specific purposes:

* [ContactBlock](#contact_block) - for adding contacts to a page
* [FeedBlock](#feed_block) - for adding rss feeds to a page
* [LinkBlock](#link_block) - to add a collection of links to a page

## ContactBlock <a id="contact_block"></a>

This model can be used to add contacts to a page. To use it you need to define a relationship in Page.php:

	public static $has_many = array(
		'Contacts'	=> 'ContactBlock',
	);

Run /dev/build to register the relationship and then add a manager for the ContactBlock class in getCmsFields():

	class Page extends SiteTree
	{
		function getCMSFields()
		{
			$fields = parent::getCMSFields();
			//                             Tab              Manager    Source Class     Title        DataList
			//                              |                  |            |             |              |
			$fields->addFieldToTab("Root.Contacts", new Block_Manager('ContactBlock', 'Contacts', $this->Contacts()) );
			return $fields;
		}
	}

Contacts can be added to template output like any other block (see [Templating](#templating)).
The extra fields that can be printed separately are:

* Title: Name of the contact
* Role: Role (job title)
* Email: E-mail address
* Phone: Phone number
* Mobile: Mobile phone number
* Street: Address
* Zip: Zipcode
* City: City
* Box: PO Box
* Image: Photo of the contact

## FeedBlock <a id="feed_block"></a>

This model can be used to add RSS feeds to a page. To use it you need to define a relationship in Page.php:

	public static $has_many = array(
		'BlogFeeds'	=> 'FeedBlock',
	);

Run /dev/build to register the relationship and then add a manager for the FeedBlock class in getCmsFields():

	class Page extends SiteTree
	{
		function getCMSFields()
		{
			$fields = parent::getCMSFields();
			//                              Tab                  Manager   Source Class    Title          DataList
			//                               |                      |           |            |                |
			$fields->addFieldToTab("Root.BlogFeeds", new FeedBlock_Manager('FeedBlock', 'Blog Feeds', $this->BlogFeeds()) );
			return $fields;
		}
	}

Feeds can be added to template output like any other block (see [FeedBlock templating](#feed_block_templating)).
The extra fields that can be printed separately are:

* FeedURL: URL to feed
* URL: Link URL (for block, not per item)
* IsExternal: True if block link is external
* IsInternal: True if block link is internal
* Image: Image
* Items: The entries in the feed

Additionally, each entry in the feed has the following fields that can be printed separately:

* Title: Title
* Summary: Shortened description (100 chars)
* Description: Description (text)
* Date: Date (SS_DateTime object)
* Link: Link to original entry

### FeedBlock templating <a id="feed_block_template_output"></a>

Since feed blocks have another dimension in that each feed contains a number of entries, the template needs to have
a nested loop in order to display the items as well as the feeds:

	<% loop BlogFeeds %>
		<!-- Check if feed has any items -->
		<% if Items %>
		<div class="feed">
			$Image.SetWidth(50)
			<% if URL %>
				<a href="$URL" <% if IsExternal %>class="external"<% end_if%>>$Title</a>
			<% else %>
				<strong>$Title</strong>
			<% end_if %>
			<br/>
			<span class="discrete">Feed Source: <a href="$FeedURL" class="external">$FeedURL</a></span><br/>
			<!-- Iterate over each item in feed -->
			<% loop Items %>
				<a href="$Link" class="external">$Title</a><br/>
				<span class="date">$Date.Format(Y-m-d H:i:s)</span><br/>
				$Description<br/>
				<br/>
			<% end_loop %>
		</div>
		<% end_if %>
	<% end_loop %>

### Global feeds

In some cases you may want to create global feeds for an entire site. For example, you may wish to define feeds on the
home page and let every other page inherit those feeds. In that case you can create a function in the Page_Controller
class that controls which feeds are returned to the loop:

	class Page_Controller extends ContentController
	{
		function BlogFeeds($limit='')
		{
			$bt = defined('DB::USE_ANSI_SQL') ? "\"" : "`";
			if(class_exists("HomePage")) {
				if($HomePage = Translatable::get_one_by_locale('HomePage', Translatable::get_current_locale())) {
					$id = $HomePage->ID;
					return DataObject::get("Block","{$bt}Block{$bt}.{$bt}ClassName{$bt} = 'BlogFeed' AND {$bt}Block{$bt}.{$bt}PageID{$bt} = '{$id}'","","",$limit);
				}
			}
			return null;
		}
	}

### Modifying feed items

FeedBlock allows you to create modifier functions to run entries through before displaying them on a page. To specify
which methods are available as modifiers you need to extend FeedBlock and create the functions you need.

Example: BlogFeed.php (place it in the same directory as Page.php, register the relationship and run /dev/build)

	class BlogFeed extends FeedBlock
	{

		/**
		 * Define which functions are available as modifiers
		 */
		protected $modifier_functions = array(
			'MyFunction',
			'MyOtherFunction',
		);

		/**
		 * Modify feed results
		 * @param DataObject $entry
		 */
		public function MyFunction($entry)
		{
			// Remove images from entry
			$entry->description = preg_replace("/(<img )([^>]*)(>)/isU", "", $entry->description);
		}

		/**
		 * Modify feed results
		 * @param DataObject $entry
		 */
		public function MyOtherFunction($entry)
		{
			// Remove links from entry
			$entry->description = preg_replace("/(<a )([^>]*)(>)/isU", "", $entry->description);
			$entry->description = str_replace("</a>", "", $entry->description);
		}

	}

When editing a BlogFeed item in a manager, one of the specified modifier functions can be selected for the entries
in that feed. Each item is then passed to the selected modified function before it's displayed on the page.

## LinkBlock <a id="link_block"></a>

This model can be used to add a collection of links to a page. To use it you need to define a relationship in Page.php:

	public static $has_many = array(
		'Links'	=> 'LinkBlock',
	);

Run /dev/build to register the relationship and then add a manager for the LinkBlock class in getCmsFields():

	class Page extends SiteTree
	{
		function getCMSFields()
		{
			$fields = parent::getCMSFields();
			//                            Tab            Manager   Source Class  Title   DataList items
			//                             |                |           |          |           |
			$fields->addFieldToTab("Root.Links", new Block_Manager('LinkBlock', 'Links', $this->Links()) );
			return $fields;
		}
	}

Links can be added to template output like any other block (see [Templating](#templating)).
The extra fields that can be printed separately are:

* Title: Link title
* URL: Link URL
* IsExternal: True if link is external
* IsInternal: True if link is internal
* Attributes: Custom attributes to add to a-tag
* CssClasses: Classnames to add to &lt;a&gt;-tag
* Image: Image

