<?php
// Load XML file
$xml = new DOMDocument;
$xml->load('http://localhost/playlist/xml/nqdTIS_B64I7zbB_tPgvHiFTnmIqpT0u');

// Load XSL file
$xsl = new DOMDocument;
$xsl->load('xsl_stylesheet.xsl');

// Configure the transformer
$proc = new XSLTProcessor;

// Attach the xsl rules
$proc->importStyleSheet($xsl);

echo $proc->transformToXML($xml);
