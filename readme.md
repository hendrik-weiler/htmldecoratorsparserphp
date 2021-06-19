# htmldecoratorsparser php

This class parses decorator annotations from html and replaces variable placeholders.

*This is the parser of the [htmldecorators](https://github.com/hendrik-weiler/htmldecorators) project*

#### Example

Data: test3 = hello, text = Lorem ipsum
```
@DecoratorName
@Decorator2()
@Decorator3
<ul></ul>

@DecWithParamVariable(
    path=${test3},
    text="Hello World",
    textWithoutQuotes=Without yes
)
<p>Hello World</p>

<h1>${text}</h1>
```

The result will be in html:
```
<ul data-dec-id="htmldec162403918934013120955"></ul>

<p data-dec-id="htmldec16240391893388115993">Hello World</p>

<h1>Lorem ipsum</h1>
```

The definition would look like this:
```
-------
Id=htmldec162403918934013120955
Name=DecoratorName
-------
Id=htmldec162403918934013120955
Name=Decorator2
-------
Id=htmldec162403918934013120955
Name=Decorator3
-------
Id=htmldec16240391893388115993
Name=DecWithParamVariable
Key=path, Value=hello // note that the variable was set into the definition and was not replaced in the html
Key=text, Value=hello world
Key=textWithoutQuotes, Value=Without yes
```

#### Usage

```
require_once 'src/HTMLDecorator/Parser.php';
...
$IdMap = array();

$parser = new HTMLDecorator\Parser($IdMap);

// without data
$htmlOutput = parser.Parse($htmlstring);
$parser->DecoratorList; // The list of extracted decorators

// with data
$data = array();
$data['value1'] = "Value 1";
$data['headline'] = "Headline";

$htmlOutput = parser.Parse($htmlstring, $data);
$parser->DecoratorList;
```