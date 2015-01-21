<?php
/**
 * TYPO3SniffsPool_Commenting_FunctionDocCommentSniff
 *
 * PHP version 5
 *
 * @category  Commenting
 * @package   TYPO3_PHPCS_Pool
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @author    Andy Grunwald <andygrunwald@gmail.com>
 * @author    Stefano Kowalke <blueduck@gmx.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2015 Stefano Kowalke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @link      https://github.com/typo3-ci/TYPO3SniffPool
 */

/**
 * Parses and verifies the doc comments for functions / methods.
 *
 * Verifies that :
 * <ul>
 *  <li>A doc comment exists</li>
 *  <li>A doc comment is made by "/**"-Comments.</li>
 *  <li>A doc comment is not empty.</li>
 *  <li>There is no blank newline before the description.</li>
 *  <li>There is a blank newline between the description and tags.</li>
 *  <li>Parameter names represent those in the method.</li>
 *  <li>Parameter comments are in the correct order</li>
 *  <li>Parameter comments are complete</li>
 *  <li>Parameter comments are correct aligned via tabs</li>
 *  <li>A return type exists</li>
 *  <li>Any throw tag must have an exception class.</li>
 * </ul>
 *
 * @category  Commenting
 * @package   TYPO3SniffPool
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @author    Andy Grunwald <andygrunwald@gmail.com>
 * @author    Stefano Kowalke <blueduck@gmx.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2015 Stefano Kowalke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @link      https://github.com/typo3-ci/TYPO3SniffPool
 */
class TYPO3SniffPool_Sniffs_Commenting_FunctionDocCommentSniff extends Squiz_Sniffs_Commenting_FunctionCommentSniff
{
    // TODO: Copy the processThrows here too
    // @throws dont need a comment

    // TODO: Copy the processReturn here too
    // return type need to be in short form int instead of integer

    protected static $allowedTypes = array(
                                      'array',
                                      'bool',
                                      'float',
                                      'int',
                                      'mixed',
                                      'object',
                                      'string',
                                      'resource',
                                      'callable',
                                     );

    /**
     * Process the function parameter comments.
     *
     * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the current token
     *                                           in the stack passed in $tokens.
     * @param int                  $commentStart The position in the stack where
     *                                           the comment started.
     *
     * @throws \PHP_CodeSniffer_Exception
     * @return void
     */
    protected function processParams(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $commentStart)
    {
        $tokens = $phpcsFile->getTokens();

        $params  = array();
        $maxType = 0;
        $maxVar  = 0;

        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] !== '@param') {
                continue;
            }

            $type         = '';
            $typeSpace    = 0;
            $var          = '';
            $varSpace     = 0;
            $comment      = '';
            $commentLines = array();
            if ($tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING) {
                $matches = array();
                preg_match('/([^$&]+)(?:((?:\$|&)[^\s]+)(?:(\s+)(.*))?)?/', $tokens[($tag + 2)]['content'], $matches);

                $typeLen   = strlen($matches[1]);
                $type      = trim($matches[1]);
                $typeSpace = ($typeLen - strlen($type));
                $typeLen   = strlen($type);
                if ($typeLen > $maxType) {
                    $maxType = $typeLen;
                }

                if (isset($matches[2]) === true) {
                    $var    = $matches[2];
                    $varLen = strlen($var);
                    if ($varLen > $maxVar) {
                        $maxVar = $varLen;
                    }

                    if (isset($matches[4]) === true) {
                        $varSpace       = strlen($matches[3]);
                        $comment        = $matches[4];
                        $commentLines[] = array(
                                           'comment' => $comment,
                                           'token'   => ($tag + 2),
                                           'indent'  => $varSpace,
                                          );

                        // Any strings until the next tag belong to this comment.
                        if (isset($tokens[$commentStart]['comment_tags'][($pos + 1)]) === true) {
                            $end = $tokens[$commentStart]['comment_tags'][($pos + 1)];
                        } else {
                            $end = $tokens[$commentStart]['comment_closer'];
                        }

                        for ($i = ($tag + 3); $i < $end; $i++) {
                            if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                                $indent = 0;
                                if ($tokens[($i - 1)]['code'] === T_DOC_COMMENT_WHITESPACE) {
                                    $indent = strlen($tokens[($i - 1)]['content']);
                                }

                                $comment       .= ' '.$tokens[$i]['content'];
                                $commentLines[] = array(
                                                   'comment' => $tokens[$i]['content'],
                                                   'token'   => $i,
                                                   'indent'  => $indent,
                                                  );
                            }
                        }
                    } else {
                        $error = 'Missing parameter comment';
                        $phpcsFile->addError($error, $tag, 'MissingParamComment');
                        $commentLines[] = array('comment' => '');
                    }//end if
                } else {
                    $error = 'Missing parameter name';
                    $phpcsFile->addError($error, $tag, 'MissingParamName');
                }//end if
            } else {
                $error = 'Missing parameter type';
                $phpcsFile->addError($error, $tag, 'MissingParamType');
            }//end if

            $params[] = array(
                         'tag'          => $tag,
                         'type'         => $type,
                         'var'          => $var,
                         'comment'      => $comment,
                         'commentLines' => $commentLines,
                         'type_space'   => $typeSpace,
                         'var_space'    => $varSpace,
                        );
        }//end foreach

        $realParams  = $phpcsFile->getMethodParameters($stackPtr);
        $foundParams = array();

        foreach ($params as $pos => $param) {
            // If the type is empty, the whole line is empty.
            if ($param['type'] === '') {
                continue;
            }

            // Check the param type value.
            $typeNames = explode('|', $param['type']);
            foreach ($typeNames as $typeName) {
                $suggestedName = self::suggestType($typeName);
                if ($typeName !== $suggestedName) {
                    $error = 'Expected "%s" but found "%s" for parameter type';
                    $data  = array(
                              $suggestedName,
                              $typeName,
                             );

                    $fix = $phpcsFile->addFixableError($error, $param['tag'], 'IncorrectParamVarName', $data);
                    if ($fix === true) {
                        $content  = $suggestedName;
                        $content .= str_repeat(' ', $param['type_space']);
                        $content .= $param['var'];
                        $content .= str_repeat(' ', $param['var_space']);
                        if (isset($param['commentLines'][0]) === true) {
                            $content .= $param['commentLines'][0]['comment'];
                        }

                        $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);
                    }
                } else if (count($typeNames) === 1) {
                    // Check type hint for array and custom type.
                    $suggestedTypeHint = '';
                    if (strpos($suggestedName, 'array') !== false) {
                        $suggestedTypeHint = 'array';
                    } else if (strpos($suggestedName, 'callable') !== false) {
                        $suggestedTypeHint = 'callable';
                        // TODO: AllowedTypes are the long version but we only allow the short version
                    } else if (in_array($typeName, self::$allowedTypes) === false) {
                        $suggestedTypeHint = $suggestedName;
                    }

                    if ($suggestedTypeHint !== '' && isset($realParams[$pos]) === true) {
                        $typeHint = $realParams[$pos]['type_hint'];
                        if ($typeHint === '') {
                            $error = 'Type hint "%s" missing for %s';
                            $data  = array(
                                      $suggestedTypeHint,
                                      $param['var'],
                                     );
                            $phpcsFile->addError($error, $stackPtr, 'TypeHintMissing', $data);
                        } else if ($typeHint !== substr($suggestedTypeHint, (strlen($typeHint) * -1))) {
                            $error = 'Expected type hint "%s"; found "%s" for %s';
                            $data  = array(
                                      $suggestedTypeHint,
                                      $typeHint,
                                      $param['var'],
                                     );
                            $phpcsFile->addError($error, $stackPtr, 'IncorrectTypeHint', $data);
                        }
                    } else if ($suggestedTypeHint === '' && isset($realParams[$pos]) === true) {
                        $typeHint = $realParams[$pos]['type_hint'];
                        if ($typeHint !== '') {
                            $error = 'Unknown type hint "%s" found for %s';
                            $data  = array(
                                      $typeHint,
                                      $param['var'],
                                     );
                            $phpcsFile->addError($error, $stackPtr, 'InvalidTypeHint', $data);
                        }
                    }//end if
                }//end if
            }//end foreach

            if ($param['var'] === '') {
                continue;
            }

            $foundParams[] = $param['var'];

            // Check number of spaces after the type.
            // TODO: Only one space is needed after @param
            $spaces = ($maxType - strlen($param['type']) + 1);
            if ($param['type_space'] !== $spaces) {
                $error = 'Expected %s spaces after parameter type; %s found';
                $data  = array(
                          $spaces,
                          $param['type_space'],
                         );

                $fix = $phpcsFile->addFixableError($error, $param['tag'], 'SpacingAfterParamType', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();

                    $content  = $param['type'];
                    $content .= str_repeat(' ', $spaces);
                    $content .= $param['var'];
                    $content .= str_repeat(' ', $param['var_space']);
                    $content .= $param['commentLines'][0]['comment'];
                    $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);

                    // Fix up the indent of additional comment lines.
                    foreach ($param['commentLines'] as $lineNum => $line) {
                        if ($lineNum === 0
                            || $param['commentLines'][$lineNum]['indent'] === 0
                        ) {
                            continue;
                        }

                        $newIndent = ($param['commentLines'][$lineNum]['indent'] + $spaces - $param['type_space']);
                        $phpcsFile->fixer->replaceToken(
                            ($param['commentLines'][$lineNum]['token'] - 1),
                            str_repeat(' ', $newIndent)
                        );
                    }

                    $phpcsFile->fixer->endChangeset();
                }//end if
            }//end if

            // Make sure the param name is correct.
            if (isset($realParams[$pos]) === true) {
                $realName = $realParams[$pos]['name'];
                if ($realName !== $param['var']) {
                    $code = 'ParamNameNoMatch';
                    $data = array(
                             $param['var'],
                             $realName,
                            );

                    $error = 'Doc comment for parameter %s does not match ';
                    if (strtolower($param['var']) === strtolower($realName)) {
                        $error .= 'case of ';
                        $code   = 'ParamNameNoCaseMatch';
                    }

                    $error .= 'actual variable name %s';

                    $phpcsFile->addError($error, $param['tag'], $code, $data);
                }
            } else if (substr($param['var'], -4) !== ',...') {
                // We must have an extra parameter comment.
                $error = 'Superfluous parameter comment';
                $phpcsFile->addError($error, $param['tag'], 'ExtraParamComment');
            }//end if

            if ($param['comment'] === '') {
                continue;
            }

            // Check number of spaces after the var name.
            $spaces = ($maxVar - strlen($param['var']) + 1);
            if ($param['var_space'] !== $spaces) {
                $error = 'Expected %s spaces after parameter name; %s found';
                $data  = array(
                          $spaces,
                          $param['var_space'],
                         );

                $fix = $phpcsFile->addFixableError($error, $param['tag'], 'SpacingAfterParamName', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();

                    $content  = $param['type'];
                    $content .= str_repeat(' ', $param['type_space']);
                    $content .= $param['var'];
                    $content .= str_repeat(' ', $spaces);
                    $content .= $param['commentLines'][0]['comment'];
                    $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);

                    // Fix up the indent of additional comment lines.
                    foreach ($param['commentLines'] as $lineNum => $line) {
                        if ($lineNum === 0
                            || $param['commentLines'][$lineNum]['indent'] === 0
                        ) {
                            continue;
                        }

                        $newIndent = ($param['commentLines'][$lineNum]['indent'] + $spaces - $param['var_space']);
                        $phpcsFile->fixer->replaceToken(
                            ($param['commentLines'][$lineNum]['token'] - 1),
                            str_repeat(' ', $newIndent)
                        );
                    }

                    $phpcsFile->fixer->endChangeset();
                }//end if
            }//end if

            // Param comments must start with a capital letter and end with the full stop.
            $firstChar = $param['comment']{0};
            if (preg_match('|\p{Lu}|u', $firstChar) === 0) {
                $error = 'Parameter comment must start with a capital letter';
                $phpcsFile->addError($error, $param['tag'], 'ParamCommentNotCapital');
            }

            $lastChar = substr($param['comment'], -1);
            if ($lastChar !== '.') {
                $error = 'Parameter comment should end with a full stop';
                $phpcsFile->addWarning($error, $param['tag'], 'ParamCommentFullStop');
            }
        }//end foreach

        $realNames = array();
        foreach ($realParams as $realParam) {
            $realNames[] = $realParam['name'];
        }

        // Report missing comments.
        $diff = array_diff($realNames, $foundParams);
        foreach ($diff as $neededParam) {
            $error = 'Doc comment for parameter "%s" missing';
            $data  = array($neededParam);
            $phpcsFile->addError($error, $commentStart, 'MissingParamTag', $data);
        }

//        if (empty($params) === false) {
//            $lastParm = (count($params) - 1);
//            if (substr_count($params[$lastParm]->getWhitespaceAfter(), $this->currentFile->eolChar) !== 1) {
//                $error = 'Last parameter comment must not a blank newline after it';
//                $errorPos = ($params[$lastParm]->getLine() + $commentStart);
//                $this->currentFile->addError($error, $errorPos, 'SpacingAfterParams');
//            }
//            // Parameters must appear immediately after the comment.
//            if ($params[0]->getOrder() !== 2) {
//                $error = 'Parameters must appear immediately after the comment';
//                $errorPos = ($params[0]->getLine() + $commentStart);
//                $this->currentFile->addError($error, $errorPos, 'SpacingBeforeParams');
//            }
//            $previousParam = null;
//            foreach ($params as $param) {
//                $errorPos = ($param->getLine() + $commentStart);
//
//                // Make sure they are in the correct order,
//                // and have the correct name.
//                $pos = $param->getPosition();
//                $paramName = ($param->getVarName() !== '') ? $param->getVarName() : '[ UNKNOWN ]';
//                // Make sure the names of the parameter comment matches the
//                // actual parameter.
//                if (isset($realParams[($pos - 1) ]) === true) {
//                    // Make sure that there are only tabs used to intend the var type.
//                    if ($this->isTabUsedToIntend($param->getWhitespaceBeforeType())) {
//                        $error = 'Spaces must be used to indent the variable type; tabs are not allowed';
//                        $this->currentFile->addError($error, $errorPos, 'SpacingBeforeParamType');
//                    }
//                    // Make sure that there are only tabs used to intend the var comment.
//                    if ($this->isTabUsedToIntend($param->getWhiteSpaceBeforeComment())) {
//                        $error = 'Spaces must be used to indent the variable comment; tabs are not allowed';
//                        $this->currentFile->addError($error, $errorPos, 'SpacingBeforeParamComment');
//                    }
//                    // Make sure that there are only tabs used to intend the var name.
//                    if ($param->getVarName() && $this->isTabUsedToIntend($param->getWhiteSpaceBeforeVarName())) {
//                        $error = 'Spaces must be used to indent the variable name; tabs are not allowed';
//                        $this->currentFile->addError($error, $errorPos, 'SpacingBeforeParamName');
//                    }
//
//                    $realName = $realParams[($pos - 1) ]['name'];
//                    $foundParams[] = $realName;
//                    // Append ampersand to name if passing by reference.
//                    if ($realParams[($pos - 1) ]['pass_by_reference'] === true) {
//                        $realName = '&' . $realName;
//                    }
//                    if ($realName !== $paramName) {
//                        $code = 'ParamNameNoMatch';
//                        $data = array($paramName, $realName, $pos,);
//                        $error = 'Doc comment for var %s does not match ';
//                        if (strtolower($paramName) === strtolower($realName)) {
//                            $error.= 'case of ';
//                            $code = 'ParamNameNoCaseMatch';
//                        }
//                        $error.= 'actual variable name %s at position %s';
//                        $this->currentFile->addError($error, $errorPos, $code, $data);
//                    }
//                } else {
//                    // Throw an error if we found a parameter in comment but not in the parameter list of the function
//                    $error = 'The paramter "' . $paramName . '" at position ' . $pos . ' is superfluous, because this parameter was not found in parameter list.';
//                    $this->currentFile->addError($error, $errorPos, 'SuperFluous.ParamComment');
//                }
//                if ($param->getVarName() === '') {
//                    $error = 'Missing parameter name at position ' . $pos;
//                    $this->currentFile->addError($error, $errorPos, 'MissingParamName');
//                }
//                if ($param->getType() === '') {
//                    $error = 'Missing type at position ' . $pos;
//                    $this->currentFile->addError($error, $errorPos, 'MissingParamType');
//                }
//            }
//        }
//        $realNames = array();
//        foreach ($realParams as $realParam) {
//            $realNames[] = $realParam['name'];
//        }
        // Report and missing comments.
//        $diff = array_diff($realNames, $foundParams);
//        foreach ($diff as $neededParam) {
//            if (count($params) !== 0) {
//                $errorPos = ($params[(count($params) - 1) ]->getLine() + $commentStart);
//            } else {
//                $errorPos = $commentStart;
//            }
//            $error = 'Doc comment for "%s" missing';
//            $data = array($neededParam);
//            $this->currentFile->addError($error, $errorPos, 'MissingParamTag', $data);
//        }
    }

    /**
     * Checks if the parameter contain a tab char
     *
     * @param string $content The whitespace part inside the comment
     *
     * @return boolean
     */
    protected function isTabUsedToIntend($content)
    {
        // is a tab char in the indention?
        return preg_match('/[\t]/', $content) ? true : false;
    }

    /**
     * Returns a valid variable type for param/var tag.
     *
     * If type is not one of the standard type, it must be a custom type.
     * Returns the correct type name suggestion if type name is invalid.
     *
     * @param string $varType The variable type to process.
     *
     * @return string
     */
    public static function suggestType($varType)
    {
        if ($varType === '') {
            return '';
        }

        if (in_array($varType, self::$allowedTypes) === true) {
            return $varType;
        } else {
            $lowerVarType = strtolower($varType);
            switch ($lowerVarType) {
            case 'boolean':
                return 'bool';
            case 'double':
            case 'real':
                return 'float';
            case 'integer':
                return 'int';
            case 'array()':
                return 'array';
            }//end switch

            if (strpos($lowerVarType, 'array(') !== false) {
                // Valid array declaration:
                // array, array(type), array(type1 => type2).
                $matches = array();
                $pattern = '/^array\(\s*([^\s^=^>]*)(\s*=>\s*(.*))?\s*\)/i';
                if (preg_match($pattern, $varType, $matches) !== 0) {
                    $type1 = '';
                    if (isset($matches[1]) === true) {
                        $type1 = $matches[1];
                    }

                    $type2 = '';
                    if (isset($matches[3]) === true) {
                        $type2 = $matches[3];
                    }

                    $type1 = self::suggestType($type1);
                    $type2 = self::suggestType($type2);
                    if ($type2 !== '') {
                        $type2 = ' => '.$type2;
                    }

                    return "array($type1$type2)";
                } else {
                    return 'array';
                }//end if
            } else if (in_array($lowerVarType, self::$allowedTypes) === true) {
                // A valid type, but not lower cased.
                return $lowerVarType;
            } else {
                // Must be a custom type name.
                return $varType;
            }//end if
        }//end if

    }//end suggestType()
}