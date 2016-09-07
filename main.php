<?php

class InlineCSSParser {
	private $dom, $html, $xpath;

	function loadFile($filename) {
		$html = file_get_contents($filename);
		$this->loadString($html);
	} 

	function loadString($html) {
		$this->html = $html;
		$this->dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$this->dom->loadHTML($html);
		$this->xpath = new DOMXPath($this->dom);
		libxml_use_internal_errors(false);

	}

	function getCSS() {
		if (empty($this->html))
			throw new Exception("No html supplied", 1);
		
		$externalCSS = "";
		
		$styleElement = $this->xpath->query("//*[@style]");		
		if (!empty($styleElement)) {
			
			foreach($styleElement as $elem) {
				$value = rtrim($elem->getAttribute("style"), ";");
				$value = trim($value);
				$css = $this->getCSSPath($elem) . " {\n";
				$css .= $this->indentCSS($value);
				$css .= "}\n\n";
				$externalCSS .= $css;
				$elem->removeAttribute("style");
			}
		}	
		return $externalCSS;
	}

	function getCSSInFile($filename) {
		return file_put_contents($filename, $this->getCSS());
	}

	function getCSSPath($elem) {
		if (empty($elem)) return "";
		$path = array();
		while ($elem->nodeType == XML_ELEMENT_NODE) {
			$name = $elem->nodeName;
			if ($id = $elem->getAttribute("id")) {
				$path[] = "#" . $id;
				break;				
			}
			else if ($class = $elem->getAttribute("class")) {
				$class = explode(" ", $class);
				$path[] = $name . "." . $class[0];
				break;
			}
			else {
				$sib = $elem;
				$nth = 1;
		            	while ($sib = $sib->previousSibling) { 
            				if ($sib->nodeName == $name) $nth++;
            			}
            	
		            	if ($nth != 1)
 			          	$path[] = $name . ":nth-child(" . $nth . ")";
				else 
					$path[] = $name;
			}
			$elem = $elem->parentNode;
		}
		$path = array_reverse($path);
		return implode(" > ", $path);
	}

	function indentCSS($css) {
		$css = explode(";", $css);
		$css = implode(";\n    ", $css);
		$css = "    " . $css;
		$css .= ";\n"; 
		return $css;
	}

	function getHTML() {
		return $this->dom->saveHTML();
	}

	function getHTMLInFile($filename) {
		return file_put_contents($filename, $this->getHTML());
	}
}

$parse = new InlineCSSParser();
$parse->loadFile("sample\\sample1.html");      // Sample file
$parse->getCSSInFile("output\\sample1.css");   // All inline styles 
$parse->getHTMLInFile("output\\sample1.html"); // HTML without inline styles

?>
