#Stim
##An unobstrusive template engine for PHP

Stim is designed to be a usable, powerful template editor. Like most template engines it separates HTML from PHP code, however unlike most template engines the template files contain no markup. Stim uses classes and ids with a jQuery-like syntax adapted for content insertion:

    $page = new Stim(array("file" => "Template.htm"));
    $page->find("#menu .title")->text("The title of my website!")->source("http://mywebsite.com/");

If you're a designer/developer this is great because you can work with HTML in the same way for PHP and javascript with the same selectors.

However if you're a developer worried about those *pesky designers* messing around with your classes and ids, you can use Stim's custom selector, stim:id, to identify content. This attribute isn't unique or singular so you can use it just like the class attribute:

	$page = new Stim(array("string" =>
		"<html>
			<div stim:id="menu">
				<h1 stim:id="title large">The wrong title!</h1>
			</menu>
		</html>"
	));
    $page->find("@menu @title")->text("The right title!");
	
The attribute is stripped out of the final page, so your HTML remains valid!