<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SemanticVersionChecker\Test\Unit\Console\Command\CompareSourceCommandTest;

use \DOMDocument;
use DOMXPath;
use Exception;
use Magento\SemanticVersionChecker\Console\Command\CompareSourceCommand;
use PHPSemVerChecker\SemanticVersioning\Level;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Defines an abstract base class for testing
 * {@link \Magento\SemanticVersionChecker\Console\Command\CompareSourceCommand}.
 */
abstract class AbstractHtmlTestCaseForHtml extends TestCase
{
    /**
     * @var CompareSourceCommand
     */
    protected $command;

    /**
     * @var string
     */
    protected $svcLogPath;

    protected function setUp()
    {
        $this->command = new CompareSourceCommand();
        $this->svcLogPath = TESTS_TEMP_DIR . '/svc-' . time() . '.html';
    }

    protected function tearDown()
    {
        parent::tearDown();
        unlink($this->svcLogPath);
    }

    /**
     * Executes the command that shall be tested and performs assertions.
     *
     * 1. Run semantic version checker command to compare 2 source code directories
     * 2. Assert that SVC log contains expected entries
     * 3. Assert console output
     * 4. Assert return code
     *
     * @param string $pathToSourceCodeBefore
     * @param string $pathToSourceCodeAfter
     * @param $allowedChangeLevel
     * @param HtmlParseInfoContainer[] $expectedHtmlEntries
     * @param array $expectedPackageSection
     * @param string $expectedOutput
     * @param $expectedStatusCode
     * @param bool $shouldSkipTest
     * @throws Exception
     */
    protected function doTestExecute(
        $pathToSourceCodeBefore,
        $pathToSourceCodeAfter,
        $allowedChangeLevel,
        $expectedHtmlEntries,
        $expectedPackageSection,
        $expectedOutput,
        $expectedStatusCode,
        $shouldSkipTest
    ): void {
        try {
            $commandTester = $this->executeCommand($pathToSourceCodeBefore, $pathToSourceCodeAfter, $allowedChangeLevel);
            $svcDom = $this->getSvcReportDOM();
            self::assertJsonContent($expectedPackageSection, $svcDom);
            foreach ($expectedHtmlEntries as $expectedHtmlEntry) {
                $this->assertHtml($expectedHtmlEntry->xpath, $expectedHtmlEntry->pattern, $svcDom);
            }
            $this->assertContains($expectedOutput, $commandTester->getDisplay());
            $this->assertEquals($expectedStatusCode, $commandTester->getStatusCode());
        } catch (Exception $e) {
            if ($shouldSkipTest) {
                $this->markTestSkipped($e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    private static function assertJsonContent(array $expectedJson, DOMDocument $docDom) {
        if (!$expectedJson) {
            $xpathQuery = '/html/body/table/tbody/tr[last()]/td[2]';
            $pattern = '#No BIC changes found to packages#i';
            self::assertHtml($xpathQuery, $pattern, $docDom);
        } else {
            $docXpath = new DOMXPath($docDom);
            $xpathQuery = '//*[@id="packageChangesJson"]/text()';
            static::assertHtml($xpathQuery, null, $docDom);
            $jsonText = $docDom->saveHTML($docXpath->query($xpathQuery)->item(0));
            $encodedJson = json_decode($jsonText);
            //store expectedJson in same format
            $expectedJson = json_decode(json_encode($expectedJson));
            self::assertEquals(sort($expectedJson), sort($encodedJson));
        }
    }

    /**
     * Assert HTML document resolves xpath, finding pattern, or finding pattern within resolving xpath
     * @param HtmlParseInfoContainer $container
     * @param DOMXPath $docXpath
     */
    public static function assertHtml(?string $xpathQuery, ?string $pattern, DOMDocument $docDom) {
        $docXpath = new DOMXPath($docDom);

        if ($xpathQuery) {
            $nodeList = $docXpath->query($xpathQuery);
            if (!$nodeList || !$nodeList->length) {
                $body = $docXpath->document->saveHTML();
                static::fail('xpath selector: ' . $xpathQuery . " was invalid. Unable to return result from document:\n" . $body); //throws exception
            }
            $body = $docDom->saveHTML($nodeList->item(-1));
        }
        else {
            $body = $docXpath->document->saveHTML();
        }
        if ($pattern) {
            static::assertRegExp($pattern, $body);
        }
    }


    /**
     * Executes {@link CompareSourceCommandTest::$command} via {@link CommandTester}, using the arguments as command
     * line parameters.
     *
     * The command line parameters are specified as follows:
     * <ul>
     *   <li><kbd>source-before</kbd>: The content of the argument <var>$pathToSourceCodeBefore</var></li>
     *   <li><kbd>source-after</kbd>: The content of the argument <var>$pathToSourceCodeAfter</var></li>
     *   <li><kbd>--log-output-location</kbd>: The content of {@link CompareSourceCommandTest::$svcLogPath}</li>
     *   <li><kbd>--include-patterns</kbd>: The path to the file <kbd>./_files/application_includes.txt</kbd></li>
     * </ul>
     *
     * @param $pathToSourceCodeBefore
     * @param $pathToSourceCodeAfter
     * @param $allowedChangeLevel
     * @return CommandTester
     */
    protected function executeCommand($pathToSourceCodeBefore, $pathToSourceCodeAfter, $allowedChangeLevel): CommandTester
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(
            [
                'source-before'         => $pathToSourceCodeBefore,
                'source-after'          => $pathToSourceCodeAfter,
                '--log-output-location' => $this->svcLogPath,
                '--include-patterns'    => __DIR__ . '/_files/application_includes.txt',
                'allowed-change-level'  => $allowedChangeLevel,
            ]
        );
        return $commandTester;
    }

    /**
     * Returns the contents of the file specified in {@link CompareSourceCommandTest::$svcLogPath}.
     *
     * @return DOMDocument
     */
    private function getSvcReportDOM() : ?DOMDocument
    {
        $source = file_get_contents($this->svcLogPath);
        if (!$source) {
            return null;
        }
        $doc = new DOMDocument();
        $doc->loadHTML($source);
        return $doc;
    }
}
