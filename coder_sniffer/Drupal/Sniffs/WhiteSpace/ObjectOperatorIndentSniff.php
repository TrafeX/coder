<?php
/**
 * Drupal_Sniffs_WhiteSpace_ObjectOperatorIndentSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Drupal_Sniffs_WhiteSpace_ObjectOperatorIndentSniff.
 *
 * Checks that object operators are indented 2 spaces if they are the first
 * thing on a line.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.2.0RC3
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_WhiteSpace_ObjectOperatorIndentSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_OBJECT_OPERATOR);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check that there is only whitespace before the object operator and there
        // is nothing lese on the line.
        if ($tokens[($stackPtr - 1)]['code'] !== T_WHITESPACE || $tokens[($stackPtr - 1)]['column'] !== 1) {
            return;
        }

        $previousLine = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, $stackPtr - 2, null, true, null, true);

        if ($previousLine === false) {
            return;
        }

        $startOfLine = $previousLine;
        while ($tokens[$startOfLine - 2]['line'] === $tokens[$startOfLine - 1]['line']) {
            $startOfLine--;
        }

        $addiotionalIndent = 0;
        if ($tokens[$startOfLine]['code'] !== T_OBJECT_OPERATOR) {
            $addiotionalIndent += 2;
        }

        if ($tokens[$stackPtr]['column'] !== ($tokens[$startOfLine]['column'] + $addiotionalIndent)) {
            $error = 'Object operator not indented correctly; expected %s spaces but found %s';
            $data  = array(
                      $tokens[$startOfLine]['column'] + $addiotionalIndent + 1,
                      $tokens[$stackPtr]['column'] + 1,
                     );
            $phpcsFile->addError($error, $stackPtr, 'Indent', $data);
        }

        //print_r($tokens[$startOfLine]);
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process2(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Make sure this is the first object operator in a chain of them.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($prev === false || $tokens[$prev]['code'] !== T_VARIABLE) {
            return;
        }

        // Make sure this is a chained call.
        $next = $phpcsFile->findNext(
            T_OBJECT_OPERATOR,
            ($stackPtr + 1),
            null,
            false,
            null,
            true
        );

        if ($next === false) {
            // Not a chained call.
            return;
        }

        // Determine correct indent of the line where the object variable is
        // located.
        for ($i = ($prev - 1); $i >= 0; $i--) {
            if ($tokens[$i]['line'] !== $tokens[$prev]['line']) {
                $i++;
                break;
            }
        }

        $requiredIndent = 0;
        if ($i >= 0 && $tokens[$i]['code'] === T_WHITESPACE) {
            $requiredIndent = strlen($tokens[$i]['content']);
        }

        $requiredIndent += 2;

        // Determine the scope of the original object operator.
        $origBrackets = null;
        if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
            $origBrackets = $tokens[$stackPtr]['nested_parenthesis'];
        }

        $origConditions = null;
        if (isset($tokens[$stackPtr]['conditions']) === true) {
            $origConditions = $tokens[$stackPtr]['conditions'];
        }

        // Start with the first operator, it might already be on a new line.
        $next = $stackPtr;

        // Check indentation of each object operator in the chain.
        while ($next !== false) {
            // Make sure it is in the same scope, otherwise don't check indent.
            $brackets = null;
            if (isset($tokens[$next]['nested_parenthesis']) === true) {
                $brackets = $tokens[$next]['nested_parenthesis'];
            }

            $conditions = null;
            if (isset($tokens[$next]['conditions']) === true) {
                $conditions = $tokens[$next]['conditions'];
            }

            if ($origBrackets === $brackets && $origConditions === $conditions) {
                // Make sure it starts a line, otherwise don't check indent.
                $indent = $tokens[($next - 1)];
                if ($indent['code'] === T_WHITESPACE) {
                    if ($indent['line'] === $tokens[$next]['line']) {
                        $foundIndent = strlen($indent['content']);
                    } else {
                        $foundIndent = 0;
                    }

                    if ($foundIndent !== $requiredIndent) {
                        $error = 'Object operator not indented correctly; expected %s spaces but found %s';
                        $data  = array(
                                  $requiredIndent,
                                  $foundIndent,
                                 );
                        $fix   = $phpcsFile->addFixableError($error, $next, 'Indent', $data);
                        if ($fix === true) {
                            $phpcsFile->fixer->replaceToken(($next - 1), str_repeat(' ', $requiredIndent));
                        }
                    }
                }

                // It can't be the last thing on the line either.
                $content = $phpcsFile->findNext(T_WHITESPACE, ($next + 1), null, true);
                if ($tokens[$content]['line'] !== $tokens[$next]['line']) {
                    $error = 'Object operator must be at the start of the line, not the end';
                    $fix   = $phpcsFile->addFixableError($error, $next, 'LineStart');
                    if ($fix === true) {
                        for ($i = ($next + 1); $i < $content ; $i++) {
                            $phpcsFile->fixer->replaceToken($i, '');
                        }

                        $phpcsFile->fixer->addContentBefore($next, "\n".str_repeat(' ', $requiredIndent));
                    }
                }
            }//end if

            $next = $phpcsFile->findNext(
                T_OBJECT_OPERATOR,
                ($next + 1),
                null,
                false,
                null,
                true
            );
        }//end while

    }//end process()


}//end class
