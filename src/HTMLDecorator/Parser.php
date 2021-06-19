<?php
/**
 * Copyright 2021 Hendrik Weiler
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace HTMLDecorator;

/**
 * The definition class for a decorator
 *
 * @author Hendrik Weiler
 * @class
 */
class DecoratorDef {
    /**
     * Returns the unique id of the decorator
     *
     * @var
     */
    public $Id;
    /**
     * Returns the name of the decorator
     *
     * @var
     */
    public $Name;

    /**
     * Returns a map of parameters
     *
     * @var
     */
    public $Params = array();

    /**
     * Checks if a parameter was already set
     *
     * @param string $key The key of the parameter
     * @return bool
     */
    public function paramExist($key) {
        return isset($this->Params[$key]);
    }
    /**
     * Sets a parameter
     *
     * @param string $key The key of the parameter
     * @param string $value The value of the parameter
     * @return void
     */
    public function setParameter($key,$value) {
        $this->Params[$key] = $value;
    }
}

/**
 * Parses html and extract the decorators
 *
 * @author Hendrik Weiler
 * @class
 */
class Parser
{
    /**
     * Returns a reference to a map for storing all ids
     *
     * @var
     */
    private $genIdsMap;

    /**
     * Returns a list of DecoratorDef instances
     *
     * @var
     */
    public $DecoratorList;

    /**
     * Generates an id
     *
     * @return string
     */
    public function GenerateId() {
        $id = '';
        while (true) {
            $id = "htmldec" . time() . rand(0,99999999);
            if(!isset($this->genIdsMap[$id])) {
                break;
            }
        }
        // set the id as index
        $this->genIdsMap[$id] = 1;

        return $id;
    }

    /**
     * The constructor
     *
     * @param array $genIdsMap The id storage map
     */
    public function __construct(Array &$genIdsMap)
    {
        $this->genIdsMap = $genIdsMap;
    }

    /**
     * Parses html with data
     *
     * @param string $value The html to parse
     * @param array $data The data to inject
     * @return string
     */
    public function Parse($value, $data = array()) {

        $this->DecoratorList = array();

        $i = 0;
        $j = 0;
        $ch = '';
        $ch2 = '';
        $len = strlen($value);
        $afterParseValue = '';
        $currentDecorator = null;
        $currentDecoratorName = '';
        $currentDecoratorParameterKey = '';
        $currentDecoratorParameterValue = '';
        $currentDecoratorId = '';
        $afterCloseTag = true;
        $inDectoratorbeforeName = false;
        $inParameterDefinition = false;
        $inParameterKey = false;
        $inParameterValue = false;
        $inParameterValueQuotes = false;
        $afterDecoratorNodeConnection = false;
        $afterDecoratorNodeOpenTag = false;
        $atFollowingClosingTag = false;
        $atFollowingOpeningTag = false;
        $inVariableDefinition = false;
        $inVariableDefinitionNextIsBracket = false;
        $inDecoratorInNameCounter = 0;
        $inParameterValueCounter = 0;
        $inVariableName = '';

        for(; $i < $len; ++$i) {
            $ch = $value[$i];
            if($inVariableDefinition) {
                if($ch == '}') {
                    if($inParameterValue) {
                        if(!isset($data[$inVariableName])) {
                            $currentDecorator->setParameter(
                                $currentDecoratorParameterKey,
                                '${' . $inVariableName . ':not found}'
                            );
                        } else {
                            $currentDecorator->setParameter(
                                $currentDecoratorParameterKey,
                                $data[$inVariableName]
                            );
                        }
                    } else {
                        if(!isset($data[$inVariableName])) {
                            $afterParseValue .= '${' . $inVariableName . ':not found}';
                        } else {
                            $afterParseValue .= $data[$inVariableName];
                        }
                    }

                    if($i+1 < strlen($value) && $value[$i+1]==')' && $inParameterValue) {
                        $inVariableDefinitionNextIsBracket = true;
                        $i-=1;
                    }
                    $inVariableDefinition = false;
                } else {
                    $inVariableName .= $ch;
                }
            } else if(!$inVariableDefinition && $ch == '$' && $i+1 < $len && $value[$i+1] == '{') {
                $x = $i - 1;
                $dollorCh = '';
                $moreThanOneDollar = false;
                for(; $x > 0; --$x) {
                    $dollorCh = $value[$x];
                    if($dollorCh != '$') {
                        if($moreThanOneDollar) {
                            break;
                        }
                        $inVariableDefinition = true;
                        $inVariableName = '';
                        $i+=1;
                        break;
                    } else {
                        $moreThanOneDollar = true;
                    }
                }
            } else if(!$afterCloseTag && !$afterDecoratorNodeConnection) {
                if($ch == '@') {
                    $afterCloseTag = true;
                    $inVariableDefinitionNextIsBracket = false;
                    $i-=1;
                    continue;
                }
                $afterParseValue .= $ch;
                if($i+1 < $len && $ch == '<' && $value[$i+1] == '/') {
                    $afterCloseTag = true;
                    $inVariableDefinitionNextIsBracket = false;
                }
            } else if($afterDecoratorNodeConnection && $afterDecoratorNodeOpenTag) {
                if($ch == ' ' || $ch == '>') {
                    $afterParseValue .= " data-dec-id=\"" . $currentDecoratorId . "\" ";
                    $currentDecorator->Id = $currentDecoratorId;
                    $this->DecoratorList[] = $currentDecorator;
                    $afterDecoratorNodeConnection = false;
                    $afterDecoratorNodeOpenTag = false;
                }
                $afterParseValue .= $ch;
            } else if($afterDecoratorNodeConnection && !$afterDecoratorNodeOpenTag) {
                if ($ch == '@' || $i == $len-1) {
                    $afterDecoratorNodeConnection = false;
                    $inDectoratorbeforeName = true;

                    // set name, id and add it to the list
                    $currentDecorator->Name = $currentDecoratorName;
                    $currentDecorator->Id = $currentDecoratorId;
                    $this->DecoratorList[] = $currentDecorator;

                    // create a new decorator
                    $currentDecorator = new DecoratorDef();
                    // reset name
                    $currentDecoratorName = '';

                    $inParameterKey = false;
                    $inParameterValue = false;
                    continue;
                }
                $afterParseValue .= $ch;
                if ($ch == '<') {
                    $afterDecoratorNodeOpenTag = true;
                    $afterCloseTag = false;
                }
            } else if($afterCloseTag && $inParameterDefinition && $inParameterValue) {
                if($inParameterValueQuotes && $ch == '"' && $value[$i-1] != '\\') {
                    $inParameterValueQuotes = false;
                    $ch = '\n';
                }
                if($i+1 < $len && $value[$i+1] == ')' && !$inParameterValueQuotes) {
                    if(!$currentDecorator->paramExist($currentDecoratorParameterKey)) {
                        $paramValue = $currentDecoratorParameterValue;
                        if($ch != '\r' && $ch != '\n') {
                            $paramValue .= $ch;
                        }
                        if($inVariableDefinitionNextIsBracket) {
                            $paramValue = $currentDecoratorParameterValue;
                            $inVariableDefinitionNextIsBracket = false;
                        }

                        $currentDecorator->setParameter(
                            $currentDecoratorParameterKey,
                            $paramValue
                        );
                    }

                    $inParameterValue = false;
                    $inParameterKey = false;

                    $currentDecoratorParameterValue = '';
                    $currentDecoratorParameterKey = '';
                    continue;
                } else if($ch == ',' && !$inParameterValueQuotes) {
                    if(!$currentDecorator->paramExist($currentDecoratorParameterKey)) {
                        $currentDecorator->setParameter(
                            $currentDecoratorParameterKey,
                            $currentDecoratorParameterValue
                        );
                    }

                    $inParameterValue = false;
                    $inParameterKey = true;
                    $currentDecoratorParameterValue = '';
                    $currentDecoratorParameterKey = '';
                    continue;
                } else if($inParameterValueCounter == 0 && $ch == '"') {
                    $inParameterValueQuotes = true;
                    continue;
                }

                if($ch != '\r' && $ch != '\n') {
                    $currentDecoratorParameterValue .= $ch;
                }
                $inParameterValueCounter += 1;
            } else if($afterCloseTag && $inParameterDefinition && $inParameterKey) {
                if($ch == '=') {
                    $inParameterValue = true;
                    $inParameterKey = false;
                    $inParameterValueCounter = 0;
                    continue;
                }
                if($ch != ' ' && $ch != '\r' && $ch != '\n') {
                    $currentDecoratorParameterKey .= $ch;
                }
            } else if($afterCloseTag && $inParameterDefinition && !$inParameterKey) {
                if($ch == ')') {
                    $afterDecoratorNodeConnection = true;
                    $inParameterDefinition = false;
                } else {
                    $inParameterKey = true;
                    $currentDecoratorParameterKey = '';
                    if($ch != ' ' && $ch != '\r' && $ch != '\n') {
                        $currentDecoratorParameterKey .= $ch;
                    }
                }
            } else if($afterCloseTag && $inDectoratorbeforeName) {
                // if the char after the @ decorator is empty skip
                if($inDecoratorInNameCounter == 0 && $ch == ' ') {
                    $inDectoratorbeforeName = false;
                    // if theres multiple @ skip this decorator
                } else if($inDecoratorInNameCounter == 0 && $ch == '@') {
                    for($j=$i+1; $j < $len; ++$j) {
                        $ch2 = $value[$j];
                        if($ch2=='@') {
                            $afterParseValue .= $ch;
                            $i+=1;
                        } else {
                            break;
                        }
                    }
                    $afterParseValue .= $ch;
                    $inDectoratorbeforeName = false;
                } else if($ch == '@' || $i == $len-1) {
                    // set name, id and add it to the list
                    $currentDecorator->Name = $currentDecoratorName;
                    $currentDecorator->Id = $currentDecoratorId;
                    $this->DecoratorList[] = $currentDecorator;

                    // create a new decorator
                    $currentDecorator = new DecoratorDef();
                    // reset name
                    $currentDecoratorName = '';

                    $inParameterKey = false;
                    $inParameterValue = false;
                } else if($ch == '<') {
                    $afterParseValue .= $ch;
                    $currentDecorator->Name = $currentDecoratorName;

                    $afterDecoratorNodeConnection = true;
                    $inParameterDefinition = false;
                    $inDectoratorbeforeName = false;

                    $afterDecoratorNodeOpenTag = true;
                    $afterCloseTag = false;

                } else if($ch == '(') {
                    $inParameterDefinition = true;
                    $currentDecorator->Name = $currentDecoratorName;
                    $inDectoratorbeforeName = false;

                    $inParameterKey = false;
                    $inParameterValue = false;
                } else {
                    if($ch!='\n' && $ch != '\r') {
                        $currentDecoratorName .= $ch;
                        ++$inDecoratorInNameCounter;
                    }
                }
            } else if($afterCloseTag && !$inDectoratorbeforeName && $ch == '@') {
                $atFollowingClosingTag = false;
                $atFollowingOpeningTag = false;
                for($j=$i; $j < strlen($value); ++$j) {
                    $ch2 = $value[$j];
                    if($ch2 == '>' && $atFollowingOpeningTag) {
                        break;
                    }
                    if($ch2 == '<' && !$atFollowingOpeningTag) {
                        if($value[$j+1] == '/') {
                            $atFollowingClosingTag = true;
                        } else {
                            $atFollowingOpeningTag = true;
                        }
                        break;
                    }
                }
                if($atFollowingClosingTag) {
                    $afterParseValue .= $ch;
                    continue;
                }
                $currentDecorator = new DecoratorDef();
                $currentDecoratorId = $this->GenerateId();
                $currentDecoratorName = '';
                $inDectoratorbeforeName = true;
                $inParameterKey = false;
                $inParameterValue = false;
                $inDecoratorInNameCounter = 0;
            } else {
                $afterParseValue .= $ch;
            }
        }

        // trim parameter values and fix names
        $newlineRegex = '#\r|\n#';
        foreach($this->DecoratorList as $dec) {
            $dec->Name = preg_replace($newlineRegex,'',trim($dec->Name));
            foreach($dec->Params as $key => $val) {
                if(preg_match($newlineRegex,$key)) {
                    $newKey = preg_replace($newlineRegex,'',$key);
                    $dec->Params[$newKey] = trim($val);
                    unset($dec->Params[$key]);
                } else {
                    $dec->Params[$key] = trim($val);
                }
            }
        }

        return $afterParseValue;

    }
}