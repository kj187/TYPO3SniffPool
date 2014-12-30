<?php
/**
 * TYPO3_Sniffs_Commenting_FunctionCommentSniff
 *
 * PHP version 5
 * TYPO3 CMS
 *
 * @category  Commenting
 * @package   TYPO3SniffPool
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @author    Andy Grunwald <andygrunwald@gmail.com>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @link      https://github.com/typo3-ci/TYPO3SniffPool
 */

/**
 * Parses and verifies the doc comments for functions / methods.
 *
 * This sniff was copied and modified
 * from PEAR_Sniffs_Commenting_FunctionCommentSniff.
 * Thanks for this guys!
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
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @version   Release: @package_version@
 * @link      https://github.com/typo3-ci/TYPO3SniffPool
 */
class TYPO3SniffPool_Sniffs_Commenting_FunctionCommentSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * The name of the method that we are currently processing.
     *
     * @var string
     */
//    private $_methodName = '';

    /**
     * The position in the stack where the fucntion token was found.
     *
     * @var int
     */
//    private $_functionToken = null;

    /**
     * The position in the stack where the class token was found.
     *
     * @var int
     */
//    private $_classToken = null;

    /**
     * The function comment parser for the current method.
     *
     * @var PHP_CodeSniffer_Comment_Parser_FunctionCommentParser
     */
//    protected $commentParser = null;

    /**
     * The current PHP_CodeSniffer_File object we are processing.
     *
     * @var PHP_CodeSniffer_File
     */
//    protected $currentFile = null;

    /**
     * The spaces between the @params.
     *
     * @var int
     */
    protected $spacesBetweenParams = 1;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_FUNCTION);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $find   = PHP_CodeSniffer_Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);

        $empty = array(
                  T_DOC_COMMENT_WHITESPACE,
                  T_DOC_COMMENT_STAR,
                 );

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            // Inline comments might just be closing comments for
            // control structures or functions instead of function comments
            // using the wrong comment type. If there is other code on the line,
            // assume they relate to that code.
            $prev = $phpcsFile->findPrevious($find, ($commentEnd - 1), null, true);
            if ($prev !== false && $tokens[$prev]['line'] === $tokens[$commentEnd]['line']) {
                $commentEnd = $prev;
            }
        }

        if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT
        ) {
            $phpcsFile->addError('Missing function doc comment', $stackPtr, 'Missing');
            $phpcsFile->recordMetric($stackPtr, 'Function has doc comment', 'no');
            return;
        } else {
            $phpcsFile->recordMetric($stackPtr, 'Function has doc comment', 'yes');
        }

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $phpcsFile->addError('You must use "/**" style comments for a function comment', $stackPtr, 'WrongStyle');
            return;
        }

        if ($tokens[$commentEnd]['line'] !== ($tokens[$stackPtr]['line'] - 1)) {
            $error = 'There must be no blank lines after the function comment';
            $phpcsFile->addError($error, $commentEnd, 'SpacingAfter');
        }

        $commentStart = $tokens[$commentEnd]['comment_opener'];
        $short        = $phpcsFile->findNext($empty, $commentStart + 1, $commentEnd, true);
        if ($short === false) {
            $error = 'Doc comment is empty';
            $phpcsFile->addError($error, $commentStart, 'Empty');
            return;
        }

        // The first line of the comment should just be the /** code.
        if ($tokens[$short]['line'] === $tokens[$commentStart]['line']) {
            $error = 'The open comment tag must be the only content on the line';
            $fix   = $phpcsFile->addFixableError($error, $commentStart, 'ContentAfterOpen');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewline($commentStart);
                $phpcsFile->fixer->addContentBefore($short, '* ');
                $phpcsFile->fixer->endChangeset();
            }
        }

        // The last line of the comment should just be the */ code.
        $prev = $phpcsFile->findPrevious($empty, ($commentEnd - 1), $commentStart, true);
        if ($tokens[$prev]['line'] === $tokens[$commentEnd]['line']) {
            $error = 'The close comment tag must be the only content on the line';
            $fix   = $phpcsFile->addFixableError($error, $commentEnd, 'ContentBeforeClose');
            if ($fix === true) {
                $phpcsFile->fixer->addNewlineBefore($commentEnd);
            }
        }

        // Check for additional blank lines at the end of the comment.
        if ($tokens[$prev]['line'] < ($tokens[$commentEnd]['line'] - 1)) {
            $error = 'Additional blank lines found at end of doc comment';
            $fix   = $phpcsFile->addFixableError($error, $commentEnd, 'SpacingAfter');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = ($prev + 1); $i < $commentEnd; $i++) {
                    if ($tokens[($i + 1)]['line'] === $tokens[$commentEnd]['line']) {
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        }

        // Check for a comment description.
        if ($tokens[$short]['code'] !== T_DOC_COMMENT_STRING) {
            $error = 'Missing short description in doc comment';
            $phpcsFile->addError($error, $short, 'MissingShort');
            return;
        }

        // No extra newline before short description.
        if ($tokens[$short]['line'] !== ($tokens[$commentStart]['line'] + 1)) {
            $error = 'Doc comment short description must be on the first line';
            $fix   = $phpcsFile->addFixableError($error, $short, 'SpacingBeforeShort');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = $commentStart; $i < $short; $i++) {
                    if ($tokens[$i]['line'] === $tokens[$commentStart]['line']) {
                        continue;
                    } else if ($tokens[$i]['line'] === $tokens[$short]['line']) {
                        break;
                    }

                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        }

        // Account for the fact that a short description might cover
        // multiple lines.
        $shortContent = $tokens[$short]['content'];
        $shortEnd     = $short;
        for ($i = ($short + 1); $i < $commentEnd; $i++) {
            if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                if ($tokens[$i]['line'] === ($tokens[$shortEnd]['line'] + 1)) {
                    $shortContent .= $tokens[$i]['content'];
                    $shortEnd      = $i;
                } else {
                    break;
                }
            }
        }

        mb_internal_encoding('UTF-8');
        $firstCharIsLetter   = preg_match('|(*UTF8)\P{L}|u', mb_substr($shortContent, 0, 1)) === 0 ? true : false;
        $fistCharIsLowercase = preg_match('|(*UTF8)\p{Ll}|u', mb_substr($shortContent, 0, 1)) === 1 ? true: false;
        if ($firstCharIsLetter === true && $fistCharIsLowercase === true) {
            $error = 'Doc comment short description must start with a capital letter';
            $fix   = $phpcsFile->addFixableError($error, $short, 'ShortNotCapital');

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($short, ucfirst($tokens[$short]['content']));
                $phpcsFile->fixer->endChangeset();
            }
        } else if ($firstCharIsLetter === false) {
            $error = 'Doc comment short description must start with a capital letter, but a non-letter char found';
            $phpcsFile->addError($error, $short, 'ShortNotStartWithLetter');
        }

        $long = $phpcsFile->findNext($empty, ($shortEnd + 1), ($commentEnd - 1), true);
        if ($long !== false) {
            if ($tokens[$long]['code'] === T_DOC_COMMENT_STRING) {
                if ($tokens[$long]['line'] !== ($tokens[$shortEnd]['line'] + 2)) {
                    $error = 'There must be exactly one blank line between descriptions in a doc comment';
                    $fix   = $phpcsFile->addFixableError($error, $long, 'SpacingBetween');
                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();
                        for ($i = ($shortEnd + 1); $i < $long; $i++) {
                            if ($tokens[$i]['line'] === $tokens[$shortEnd]['line']) {
                                continue;
                            } else if ($tokens[$i]['line'] === ($tokens[$long]['line'] - 1)) {
                                break;
                            }

                            $phpcsFile->fixer->replaceToken($i, '');
                        }

                        $phpcsFile->fixer->endChangeset();
                    }
                }

                $longContent         = $tokens[$long]['content'];
                $firstCharIsLetter   = preg_match('|(*UTF8)\P{L}|u', mb_substr($longContent, 0, 1)) === 0 ? true : false;
                $fistCharIsLowercase = preg_match('|(*UTF8)\p{Ll}|u', mb_substr($longContent, 0, 1)) === 1 ? true: false;
                if ($firstCharIsLetter === true && $fistCharIsLowercase === true) {
                    $error = 'Doc comment long description must start with a capital letter';
                    $fix   = $phpcsFile->addFixableError($error, $long, 'LongNotCapital');

                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();
                        $phpcsFile->fixer->replaceToken($long, ucfirst($tokens[$long]['content']));
                        $phpcsFile->fixer->endChangeset();
                    }
                } else if ($firstCharIsLetter === false) {
                    $error = 'Doc comment long description must start with a capital letter, but a non-letter char found';
                    $phpcsFile->addError($error, $long, 'LongNotStartWithLetter');
                }
            }//end if
        }//end if

        if (empty($tokens[$commentStart]['comment_tags']) === false) {
            $firstTag = $tokens[$commentStart]['comment_tags'][0];
            $prev     = $phpcsFile->findPrevious($empty, ($firstTag - 1), $commentStart, true);
            if ($tokens[$firstTag]['line'] !== ($tokens[$prev]['line'] + 2)) {
                $error = 'There must be exactly one blank line before the tags in a doc comment';
                $fix   = $phpcsFile->addFixableError($error, $firstTag, 'SpacingBeforeTags');
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($prev + 1); $i < $firstTag; $i++) {
                        if ($tokens[$i]['line'] === $tokens[$firstTag]['line']) {
                            break;
                        }

                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $indent = str_repeat(' ', $tokens[$commentStart]['column']);
                    $phpcsFile->fixer->addContent($prev, $phpcsFile->eolChar.$indent.'*'.$phpcsFile->eolChar);
                    $phpcsFile->fixer->endChangeset();
                }
            }
        }//end if

        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            if ($tokens[$tag]['content'] === '@see') {
                // Make sure the tag isn't empty.
                $string = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $tag, $commentEnd);
                if ($string === false || $tokens[$string]['line'] !== $tokens[$tag]['line']) {
                    $error = 'Content missing for @see tag in function comment';
                    $phpcsFile->addError($error, $tag, 'EmptySees');
                }
            }
        }

        $this->processParams($phpcsFile, $stackPtr, $commentStart);
//        $this->processReturn($phpcsFile, $stackPtr, $commentStart, $commentEnd);
//        $this->processThrows($phpcsFile, $stackPtr, $commentStart);

//        // No extra newline before short description.
//        $short = $comment->getShortComment();
//        $newlineCount = 0;
//        $newlineSpan = strspn($short, $phpcsFile->eolChar);
//        if ($short !== '' && $newlineSpan > 0) {
//            $error = 'Extra newline(s) found before function comment short description';
//            $phpcsFile->addError($error, ($commentStart + 1), 'SpacingBeforeShort');
//        }
//        $newlineCount = (substr_count($short, $phpcsFile->eolChar) + 1);
//        // Exactly one blank line before tags.
//        $params = $this->commentParser->getTagOrders();
//        if (count($params) > 1) {
//            $newlineSpan = $comment->getNewlineAfter();
//            if ($newlineSpan !== 2) {
//                $error = 'There must be exactly one blank line before the tags in function comment';
//                $long = $comment->getLongComment();
//                if ($long !== '') {
//                    $newlineCount+= (substr_count($long, $phpcsFile->eolChar) - $newlineSpan + 1);
//                }
//                $phpcsFile->addError($error, ($commentStart + $newlineCount), 'SpacingBeforeTags');
//                $short = rtrim($short, $phpcsFile->eolChar . ' ');
//            }
//        }
//        return null;
    }

    /**
     * Process any throw tags that this function comment has.
     *
     * @param int $commentStart The position in the stack where the
     *                          comment started.
     *
     * @return void
     */
    protected function processThrows($commentStart)
    {
        if (count($this->commentParser->getThrows()) === 0) {
            return;
        }
        foreach ($this->commentParser->getThrows() as $throw) {
            $exception = $throw->getValue();
            $errorPos = ($commentStart + $throw->getLine());
            if ($exception === '') {
                $error = '@throws tag must contain the exception class name';
                $this->currentFile->addError($error, $errorPos, 'EmptyThrows');
            }
        }
    }

    /**
     * Process the return comment of this function comment.
     *
     * @param int $commentStart The position in the stack where the comment started.
     * @param int $commentEnd   The position in the stack where the comment ended.
     *
     * @return void
     */
    protected function processReturn($commentStart, $commentEnd)
    {
        // Skip constructor and destructor.
        $className = '';
        if ($this->_classToken !== null) {
            $className = $this->currentFile->getDeclarationName($this->_classToken);
            $className = strtolower(ltrim($className, '_'));
        }
        $methodName = strtolower(ltrim($this->_methodName, '_'));
        $isSpecialMethod = ($this->_methodName === '__construct' || $this->_methodName === '__destruct');
        if ($isSpecialMethod === false && $methodName !== $className) {
            // Report missing return tag.
            if ($this->commentParser->getReturn() === null) {
                $error = 'Missing @return tag in function comment';
                $this->currentFile->addError($error, $commentEnd, 'MissingReturn');
            } elseif (trim($this->commentParser->getReturn()->getRawContent()) === '') {
                $error = '@return tag is empty in function comment';
                $errorPos = ($commentStart + $this->commentParser->getReturn()->getLine());
                $this->currentFile->addError($error, $errorPos, 'EmptyReturn');
            }
        }
    }

    /**
     * Process the function parameter comments.
     *
     * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the current token
     * @param int                  $commentStart The position in the stack wher
     *                                           the comment started.
     *
     * @return void
     */
    protected function processParams($phpcsFile, $stackPtr, $commentStart)
    {
        // TODO: Check for short type names "int" instead of "integer"
        $tokens  = $phpcsFile->getTokens();
        $params  = array();

        foreach ($tokens[$commentStart]['comment_tags'] as $pos => $tag) {
            if ($tokens[$tag]['content'] !== '@param') {
                continue;
            }

            $type            = '';
            $typeSpace       = 0;
            $typeContainsTab = false;
            $var             = '';
            $varSpace        = 0;
            $varContainsTab  = false;
            $comment         = '';

            if ($tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING) {
                $matches = array();
                preg_match(
                    '/([^$&]+)(?:((?:\$|&)[^\s]+)(?:(\s+)(.*))?)?/',
                    $tokens[($tag + 2)]['content'],
                    $matches
                );

                // Check if param type is indent with tabs
                if ($this->isTabUsedToIntend($tokens[($tag + 1)]['content'])) {
                    $error = 'Spaces must be used to indent the variable type; tabs are not allowed';
                    $fix = $phpcsFile->addFixableError($error, $tag, 'SpacingBeforeParamType');

                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();
                        $phpcsFile->fixer->replaceToken(($tag + 1), ' ');
                        $phpcsFile->fixer->endChangeset();
                    }
                }

                $typeLen         = strlen($matches[1]);
                $type            = trim($matches[1]);
                $typeSpace       = ($typeLen - strlen($type));
                $typeContainsTab = $this->isTabUsedToIntend($matches[1]);

//                // Check if variable name is indent with tabs
//                if ($typeContainsTab === true) {
//                    // TODO: Fix the violation
//                    $error = 'Spaces must be used to indent the variable name; tabs are not allowed';
//                    $phpcsFile->addError($error, $tag, 'SpacingBeforeParamName');
//                }

                if (isset($matches[2]) === true) {
                    $var    = $matches[2];

                    if (isset($matches[4]) === true) {
                        $varSpace = strlen($matches[3]);
                        $varContainsTab = $this->isTabUsedToIntend($matches[3]);

//                        // Check if variable comment is indent with tabs
//                        if ($varContainsTab === true) {
//                            $error = 'Spaces must be used to indent the variable comment; tabs are not allowed';
//                            $phpcsFile->addError($error, $tag, 'SpacingBeforeParamComment');
//                        }
                        $comment = $matches[4];

                        // Any strings until the next tag belong to this comment.
                        if (isset($tokes[$commentStart]['comment_tags'][($pos +1 )]) === true) {
                            $end = $tokens[$commentStart]['comment_tags'][($pos + 1)];
                        } else {
                            $end = $tokens[$commentStart]['comment_closer'];
                        }

                        for ($i = ($tag + 3); $i < $end; $i++) {
                            if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
                                $comment .= ' '.$tokens[$i]['content'];
                            }
                        }
                    } else {
                        $error = 'Missing parameter comment';
                        $phpcsFile->addError($error, $tag, 'MissingParamComment');
                    }
                } else {
                    $error = 'Missing parameter name';
                    $phpcsFile->addError($error, $tag, 'MissingParamName');
                }//end if
            } else {
                $error = 'Missing parameter type';
                $phpcsFile->addError($error, $tag, 'MissingParamType');
            }//end if

            $params[] = array(
                        'tag'                    => $tag,
                        'type'                   => $type,
                        'var'                    => $var,
                        'comment'                => $comment,
                        'type_space'             => $typeSpace,
                        'type_has_tab_indention' => $typeContainsTab,
                        'var_space'              => $varSpace,
                        'var_has_tab_indention'  => $varContainsTab,
                        );
        }//end foreach

        $realParams = $phpcsFile->getMethodParameters($stackPtr);
        $foundParams = array();

        foreach ($params as $pos => $param) {
            if ($param['var'] === '') {
                continue;
            }

            $foundParams[] = $param['var'];

            // Check if variable name is indent with tabs
            if ($param['type_has_tab_indention'] === true) {
                // TODO: Fix the violation
                $error = 'Spaces must be used to indent the variable name; tabs are not allowed';
                $phpcsFile->addError($error, $param['tag'], 'SpacingBeforeParamName');
            }

            if ($param['type_space'] !== $this->spacesBetweenParams && $param['type_has_tab_indention'] === false) {
                // Check number of spaces after type.
                $error = 'Expected %s space after parameter type; %s found';
                $data = array(
                        $this->spacesBetweenParams,
                        $param['type_space'],
                        );

                $fix = $phpcsFile->addFixableError(
                    $error,
                    $param['tag'],
                    'SpacingAfterParamType',
                    $data
                );

                if ($fix === true) {
                    $content  = $param['type'] . ' ';
                    $content .= $param['var'] . ' ';
                    $content .= $param['comment'];
                    $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);
                }
            }//end if

            // Make sure the param name is correct
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

            // Check if variable comment is indent with tabs
            if ($param['var_has_tab_indention'] === true) {
                $error = 'Spaces must be used to indent the variable comment; tabs are not allowed';
                $phpcsFile->addError($error, $param['tag'], 'SpacingBeforeParamComment');
            }

            if ($param['var_space'] !== $this->spacesBetweenParams) {
                // Check the number of spaces after the var name.
                $error = 'Expected %s space after parameter name; %s found.';
                $data = array(
                        $this->spacesBetweenParams,
                        $param['var_space'],
                        );

                $fix = $phpcsFile->addFixableError(
                    $error,
                    $param['tag'],
                    'SpacingAfterParamName',
                    $data
                );

                if ($fix === true) {
                    $content  = $param['type'] . ' ';
                    $content .= $param['var'] . ' ';
                    $content .= $param['comment'];
                    $phpcsFile->fixer->replaceToken(($param['tag'] + 2), $content);
                }
            }//end if
        }//end foreach

//
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

        $realNames = array();
        foreach ($realParams as $realParam) {
            $realNames[] = $realParam['name'];
        }

        // Report and missing comments.
        $diff = array_diff($realNames, $foundParams);
        foreach ($diff as $neededParam) {
            $error = 'Doc comment for parameter "%s" is missing.';
            $data = array($neededParam);
            $phpcsFile->addError($error, $commentStart, 'MissingParamTag', $data);
        }

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
    }//end processParams()

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
}