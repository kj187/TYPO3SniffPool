<?php
/**
 * Unit test class for FunctionDocCommentSniff.
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
 * Unit test class for FunctionDocCommentSniff.
 *
 * This unit test was copied and modified
 * from PEAR.Commenting.FunctionCommentSniff.
 * Thanks for this guys!
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
class TYPO3SniffPool_Tests_Commenting_FunctionDocCommentUnitTest extends AbstractSniffUnitTest
{


    /**
     * Returns the lines where errors should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of errors that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getErrorList()
    {
        return array(
                19  => 2,
                24  => 1,
                25  => 1,
                39  => 1,
                94  => 1,
                99  => 1,
                102 => 1,
                111 => 1,
                113 => 1,
                114 => 1,
                115 => 1,
                116 => 1,
                117 => 1,
                127 => 1,
                134 => 2,
                152 => 1,
                159 => 1,
                169 => 1,
                175 => 2,
                190 => 1,
                220 => 1,

//                186 => 1,
//                197 => 1,
//                218 => 1,
//                250 => 3,
                252 => 3,
                253 => 3,
                254 => 2,
                255 => 2,
                256 => 2,
                257 => 1,
               );

    }//end getErrorList()


    /**
     * Returns the lines where warnings should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of warnings that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getWarningList()
    {
        return array(
                11 => 1,
                24 => 1,
               );

    }//end getWarningList()


}//end class
