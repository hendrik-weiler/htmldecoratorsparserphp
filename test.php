<?php

require_once 'src/HTMLDecorator/Parser.php';

$IdMap = array();

$parser = new HTMLDecorator\Parser($IdMap);

//$f1 = file_get_contents('testdata/test1.html');
//$html = $parser->Parse($f1);

//$f2 = file_get_contents('testdata/test2.html');
//$html2 = $parser->Parse($f2);

//$f3 = file_get_contents('testdata/test3.html');
//$html3 = $parser->Parse($f3);

$data = array();
$data['value1'] = "Value 1";
$data['headline'] = 'Headline';
$data['underlined'] = 'is underlined';
$data['test'] = 'Test 1';
$data['test2'] = 'Test 2';
$data['test3'] = 'Test 3';
$data['test4'] = 'Test 4';

//$f4 = file_get_contents('testdata/test4.html');
//$html4 = $parser->Parse($f4, $data);

//$f5 = file_get_contents('testdata/test5.html');
//$html5 = $parser->Parse($f5, $data);
//print $html5;
//var_dump($parser->DecoratorList);