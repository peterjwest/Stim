<?php
require("../Stim.php");

$menu = array(
	array("text" => "Home", "link" => "home.php"),
	array("text" => "Stuff", "link" => "stuff.php"),
	array("text" => "Things", "link" => "things.php")
);

$articles = array(
	array(
		"title" => "The Title A", 
		"body" => "<p>Some Content!</p>", 
		"comments" => array(
			array("body" => "My comment", "author" => "Fred")
		)
	),
	array(
		"title" => "The Title B", 
		"body" => "<p>Some Content!</p>", 
		"comments" => array()
	),
	array(
		"title" => "The Title C", 
		"body" => "<p>Some Content!</p>", 
		"comments" => array(
			array("body" => "My comment", "author" => "Fred"),
			array("body" => "Your comment?", "author" => "John")
		)
	)
);

$page = new Stim(array("file" => "Template.htm"));
$page->find("#header .title")->html("<strong>Foo Bar</strong>");
$page->find("#header .menu .item")->insert(
	$menu, function($item, $data) { $item->find(".link")->text($data['text'])->source($data['link']); }
);
$tags = new Stim(array("file" => "SubTemplate.htm"));
$page->find("#tags")->html($tags->find("#tags")->html());
$page->find("#page .article")->insert(
	$articles, 
	function($item, $data) { 
		$item->find(".title")->text($data['title']); 
		$item->find(".body")->html($data['body']); 
		$item->find(".comments .comment")->insert(
			$data['comments'],
			function($item, $data) {
				$item->find(".body")->text($data['body']);
				$item->find(".author")->text($data['author']);
			}
		);
	}
);
echo $page->html();