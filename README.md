#Stim
##An unobstrusive template engine for PHP

Stim is designed to be a usable, powerful template editor. Like most template engines it separates HTML from PHP code, however unlike most template engines, the template files contain no markup. Stim uses classes and ids with a jQuery-like syntax adapted for content insertion:

    $page = new Stim(array("file" => "Template.htm"));
    $page->find("#menu .title")->text("The title of my website!")->source("http://mywebsite.com/");
	echo $page->html();

If you're a designer/developer this is great because you can work with HTML in the same way for PHP and javascript with the same selectors.

However if you're a developer worried about those *pesky designers* messing around with your classes and ids, you can use Stim's custom selector, `stim:id`, to identify content. This attribute isn't unique or singular so you can use it just like the class attribute:

	$page = new Stim(array("string" =>
		"<html>
			<div stim:id="menu">
				<h1 stim:id="title large">The wrong title!</h1>
			</menu>
		</html>"
	));
    $page->find("@menu @title")->text("The right title!");
	
The `stim:id` attribute is stripped out of the final page for clean, valid HTML!

##Features
Stim has a single selector method supporting classes, ids and `stim:id`:

    $page->find("#header .menu @item .big.link @capital@letter")->find(".text");

Stim doesn't support element names (eg `a.link`) or alternative pattern matching (eg `.a > .b`, `.a + .b` or `.a[foo="bar"]`) because these may vary based on the template implementation. For example the designer may add a wrapper element which breaks a `.a > .b` selector, or change an `div` element to a `ul` element, breaking a `div.list` selector.

The rest of Stim's methods are getter/setters with a very similar behaviour to jQuery. If a value is passed, they will set that value for each element and return the original object, otherwise they will return the value from the first element.

- `text()` sets the inner text, removing any child nodes
- `cdata()` sets an inner cdata section, removing any child nodes
- `html()` sets the inner html, removing any child nodes
- `attr($name)` sets the $name attribute 

###HTML Shortcuts
Stim also supports two context sensitive shortcut methods: `source()` and `val()`:
- `source()` sets the source file, or hyperlink for an element, it currently supports `a`, `href`, `style`, `link`, and `form`
- `val()` sets the value of form fields, it currently supports `input`, `textarea` and `option`

###Dynamic content resizing
The final two methods are the best ones! These are for inserting lists of content, you know: articles, comments, tags, menu links, etc. These methods group selected elements which are adjacent into lists, here's an example:
    
	$page = new Stim(array("string" =>
		"<html>
			<div>
				<div class="item">1</div>
				<div class="item">2</div>
				<div class="item">3</div>
			</div>
			<div class="item">4</div>
			<div class="item">5</div>
			<br>
			<div class="item">6</div>
		</html>"
	));
    $items = $page->find(".item");
	
In this example `$items` has six elements which would be grouped into three lists, since the first three are inside a `div`, and the last three are split with a `br` element.

- `each($data, $function)` calls $function with the nth element from each list and the nth item from `$data`
- `insert($data, $function)` resizes each list to the size of `$data`, then does the same as `each()`

Here's a quick example which calls `insert()` recursively, to add comments to articles!

	$articles = array(
		array(
			"body" => "Some article!", 
			"comments" => array(array("body" => "Some comment!"))
		),
		array(
			"body" => "Some other article!", 
			"comments" => array()
		),
		array(
			"body" => "Some other, other article!", 
			"comments" => array(array("body" => "Some other comment!"), array("body" => "Some other, other comment!"))
		)
	);
	$page->find("#page .article")->insert($articles, function($item, $article) {
		$item->find(".body")->html($article['body']); 
		$item->find(".comments .comment")->insert($article['comments'], function($item, $comment) {
			$item->find(".body")->text($comment['body']);
			$item->find(".author")->text($comment['author']);
		});
	});

###Sub-Templates
Sub templates are really simple with Stim, I'm not even going to explain it:

	$page = new Stim(array("file" => "Template.htm"));
	$tags = new Stim(array("file" => "SubTemplate.htm"));
	$page->find("#tags")->html($tags->find("#tags")->html());

##Requirements and Limitations
Stim requires [PHP's DOM](http://php.net/manual/en/book.dom.php) which is automatically enabled for most PHP installations.

###Anonymous Functions
The Stim methods `insert()` and `each()` require function passing. Ideally you should use [anonymous functions](http://php.net/manual/en/functions.anonymous.php) (used in the examples) which require PHP 5.30 or above. 
However, for older versions of PHP, you can use [variable functions](http://www.php.net/manual/en/functions.variable-functions.php) instead.

###XHTML
Unfortunately PHP's DOM doesn't support XHTML correctly (self closing tags are not closed), however any other version of HTML, including HTML5, is supported.

###CDATA
PHP's DOM doesn't support CDATA when loading an HTML file, so Stim templates can't include CDATA.

##Bugs and Future Development

- The `cdata()` method uses the [createCDATASection()](http://php.net/manual/en/domdocument.createcdatasection.php) method, which doesn't add CDATA tags (this may be a bug). Currently, these tags are  added manually, and are commented out in script tags to prevent javascript errors.
- Please send bugs or suggestions to peterjwest3@gmail.com