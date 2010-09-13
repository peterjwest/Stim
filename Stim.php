<?php
class Stim extends Stim_Node {
	function elem($name) { return $this->dom->getElementsByTagName($name)->item(0); }
	
	function html() { 
		$args = func_get_args();
		if (count($args) > 0) {
			$this->dom->loadHTML($args[0]);
			return $this;
		}
		else return $this->dom->saveHTML(); 
	}
	
	function __construct($options) {
		$this->dom = new DOMDocument;
		$this->dom->registerNodeClass('DOMElement', 'StimDomElem');
		if (isset($options['file'])) $this->htmlFile($options['file']);
		if (isset($options['string'])) $this->html($options['string']);
		parent::__construct(array($this->elem("html")));
	}
	
	private function htmlFile($filename) {
		if (file_exists($filename) && $file = file_get_contents($filename)) return $this->html($file);
		throw new Exception("Template file could not be read");
	}
}

class Stim_Node {
	function __construct($elems) { $this->elems = $elems; }
	function first() { return new Stim_Node($this->elems ? array(reset($this->elems)) : array()); }
	function last() { return new Stim_Node($this->elems ? array(end($this->elems)) : array()); }
	private function head($selectors) { return array_shift($selectors); }
	private function tail($selectors) { array_shift($selectors); return $selectors; }
	
	function find($selectors) {
		$selectors = preg_split("~ +~", trim($selectors));
		$matched = array();
		foreach($this->elems as $elem) {
			$matched = array_merge($matched, $this->findElems($elem->children(), $selectors));
		}
		return new Stim_Node($matched);
	}
	
	private function findElems($elems, $selectors) {
		$matched = array();
		foreach ($elems as $elem) {
			if ($elem instanceof StimDomElem) {
				if ($this->matches($elem, $this->head($selectors))) {
					if ($this->tail($selectors)) {
						$matched = array_merge(
							$matched, $this->findElems($elem->children(), $this->tail($selectors))
						);
					}
					else $matched[] = $elem;
				}
				$matched = array_merge(
					$matched, $this->findElems($elem->children(), $selectors)
				);
			}
		}
		return $matched;
	}
	
	private function matches($elem, $selector) {
		foreach (preg_split("~(?=[\.#@])~",$selector) as $part) {
			if (substr($part,0,1) === "#")
				if ($elem->getAttribute("id") !== substr($part,1)) return false;
			if (substr($part,0,1) === ".")
				if (!in_array(substr($part,1), preg_split("~ +~", $elem->getAttribute("class")))) 
					return false;
			if (substr($part,0,1) === "@")
				if (!in_array(substr($part,1), preg_split("~ +~", $elem->getAttribute("stim:id")))) 
					return false;
		}
		return true;
	}
	
	function each($data, $function) {
		foreach ($this->lists() as $list)
			foreach ($list as $key => $elem)
				$function(new Stim_Node(array($elem)), isset($data[$key]) ? $data[$key] : array());
	}
	
	function insert($data, $function) {
		foreach ($this->lists() as $list)
			foreach ($this->resize($list, count($data)) as $key => $elem)
				$function(new Stim_Node(array($elem)), isset($data[$key]) ? $data[$key] : array());
	}
	
	private function resize($elems, $length) {
		$nodeLength = count($elems);
		if ($length === $nodeLength) return $elems;
		if ($length < $nodeLength) {
			$newNodes = array();
			foreach ($elems as $number => $node) {
				if ($number >= $length) { 
					$node->parentNode->removeChild($node);
					unset($elems[$number]);
				} 
			}
			return $elems; 
		}
		if ($length > $nodeLength) {
			while(count($elems) < $length)
				$elems[] = end($elems)->insertAfter($elems[count($elems) - $nodeLength]->cloneNode()); 
			return $elems; 
		} 
	}
	
	private function lists() {
		$lists = array();
		$curr = array();
		foreach ($this->elems as $key => $elem) {
			$prev = $elem->previousSibling;
			while($prev instanceof DOMText)
				$prev = $prev->previousSibling;
			if (!isset($this->elems[$key - 1]) || $prev === $this->elems[$key - 1])
				$curr[] = $elem;
			else {
			$lists[] = $curr;
			$curr = array($elem);
			}
		}
		$lists[] = $curr;
		return $lists;
	}
	
	function text() {
		$args = func_get_args();
		if (count($args) > 0)
			foreach ($this->elems as $elem)
				$elem->setText($args[0]);
		else if ($this->elems) return $this->elems[0]->getText();
		return $this;
	}
	
	function html() {
		$args = func_get_args();
		if (count($args) > 0)
			foreach ($this->elems as $elem)
				$elem->setHtml($args[0]);
		else if ($this->elems) return $this->elems[0]->getHtml();
		return $this;		
	}
	
	function val() {
		$args = func_get_args();
		if (count($args) > 0)
			foreach ($this->elems as $elem) {
				foreach($this->valAttr($elem) as $attr) {
					if ($attr) $elem->setAttribute($attr, htmlentities($args[0]));
					else $elem->setText($args[0]);
				}
			}
		else {
			$attr = reset($this->valAttr($elem));
			if ($attr) $elem->getAttribute($this->valAttr($this->elems[0]));
			else $elem->getText();
		}
		return $this;
	}
	
	private function valAttr($elem) {
		if (in_array($elem->tagName, array("input"))) return array("value");
		if (in_array($elem->tagName, array("option"))) return array("value", "");
		return array("");
	}

	function source() {
		$args = func_get_args();
		if (count($args) > 0)
			foreach ($this->elems as $elem)
				$elem->setAttribute($this->sourceAttr($elem), htmlentities($args[0]));
		else if ($this->elems) return $this->elems[0]->getAttribute($this->sourceAttr($this->elems[0]));
		return $this;
	}
	
	private function sourceAttr($elem) {
		if (in_array($elem->tagName, array("img", "script"))) return "src";
		if (in_array($elem->tagName, array("a", "link"))) return "href";
		if (in_array($elem->tagName, array("form"))) return "action";
		return "href";
	}
	
	function attr() {
		$args = func_get_args();
		if (count($args) > 1) {
			foreach ($this->elems as $elem) {
				if ($args[1] !== false)
					$elem->setAttribute($args[0], htmlentities($args[1]));
				else $elem->removeAttribute($args[0]);
			}
		}
		else if (count($args) > 0 && $this->elems)
			return $this->elems[0]->getAttribute($args[0]);
		return $this;
	}
}

class StimDomElem extends DOMElement {
	function children() {
		$nodes = array();
		for ($n = 0; $n < $this->childNodes->length; $n++) $nodes[] = $this->childNodes->item($n);
		return $nodes;
	}
	
	function setText($text) {
		while ($this->hasChildNodes()) {
			$this->removeChild($this->firstChild); }
		$this->appendChild($this->ownerDocument->createTextNode(htmlentities($text))); 
	}

	function setHtml($html) {
		while ($this->hasChildNodes())
			$this->removeChild($this->firstChild);
		foreach($this->createHtml($html) as $elem)
			$this->appendChild($elem); 
	}

	function createHtml($html) {
		$temp = new Stim(array('string' => $html));
		$elems = array();
		foreach($temp->elem("body")->children() as $elem)
			$elems[] = $this->ownerDocument->importNode($elem, true);
		return $elems;
	}
	
	function getHtml() {
		$temp = new Stim(array('string' => "<html><body></body></html>"));
		foreach($this->children() as $child) {
			$elem = $temp->dom->importNode($child, true);
			$temp->elem("body")->appendChild($elem);
		}
		$html = $temp->html();
		preg_match_all("~<body>|</body>~", $html, $matches, PREG_OFFSET_CAPTURE);
		$first = reset($matches[0]);
		$last = end($matches[0]);
		return substr($html, $first[1] + strlen($first[0]), $last[1] - ($first[1] + strlen($first[0])));
	}
	
	function insertAfter($node) {
		if ($this->nextSibling) $this->parentNode->insertBefore($node, $this->nextSibling);
		else $this->parentNode->appendChild($node);
		return $node;
	}
	
	function cloneNode() {
		$tempDoc = new DOMDocument();
		return $this->ownerDocument->importNode($tempDoc->importNode($this, true), true); 
	}
}