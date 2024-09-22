<?php

// =================================== PHPUnit Loader
class IDE_Remote_PHPUnit_Loader
{
    public static $supportedVersions = array(
        '4.1.0' => '41',
        '4.0.0' => '40',
        '3.8.0' => '38',
        '3.7.0' => '37',
        '3.6.0' => '36',
        '3.5.0' => '35',
        '3.4.0' => '34',
        'dev' => 'dev');

    const SUCCESS_EXIT = 0;
    const FAILURE_EXIT = 1;
    const EXCEPTION_EXIT = 2;

    public static $PHPUnitVersionId;

    /**
     * @return void
     */
    public static function checkIncludePath()
    {
        //check include path
        $PHPUnitParentDirectory = self::findFileInIncludePath('PHPUnit/');
        if (is_null($PHPUnitParentDirectory)) {
            echo "Cannot find PHPUnit in include path (" . ini_get('include_path') . ")";
            exit(IDE_Remote_PHPUnit_Loader::FAILURE_EXIT);
        }
    }

    /**
     * @param $relativePath
     * @return null | string
     */
    private static function findFileInIncludePath($relativePath)
    {
        $pathArray = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($pathArray as $path)
        {
            $filename = $path . DIRECTORY_SEPARATOR . $relativePath;
            if (file_exists($filename)) {
                return $filename;
            }
        }
        return null;
    }

    /**
     * Detect current phpunit version and convert it into version id
     *
     * @return void
     */
    private  static function detectPHPUnitVersionId()
    {
        if (!class_exists('PHPUnit_Runner_Version', true)) {
            $autoload = self::findFileInIncludePath('PHPUnit/Autoload.php');
            if (!is_null($autoload)) {
                require_once $autoload;
            }
            require_once 'PHPUnit/Runner/Version.php';
        }
        $PHPUnitVersion = PHPUnit_Runner_Version::id();

        if ($PHPUnitVersion === "@package_version@") {
            self::$PHPUnitVersionId = "37";
        }
        else {
            foreach (IDE_Remote_PHPUnit_Loader::$supportedVersions as $fullVersion => $shortVersion) {
                if (version_compare($PHPUnitVersion, $fullVersion) >= 0) {
                    self::$PHPUnitVersionId = $shortVersion;
                    break;
                }
            }

            if (self::$PHPUnitVersionId == null) {
                echo "Unsupported PHPUnit version:  $PHPUnitVersion";
                exit(IDE_Remote_PHPUnit_Loader::FAILURE_EXIT);
            }
        }
    }


    /**
     * @return void
     */
    private static function load()
    {
        //require_once 'PHPUnit/Autoload.php';
    }

    /**
     * @return void
     */
    public static function load36()
    {
        define('PHPUnit_MAIN_METHOD', 'IDE_PHPUnit_TextUI_Command::main');

        //require 'PHPUnit/Autoload.php';

    }


    /**
     * @return void
     */
    public static function load35()
    {

        require_once 'PHP/CodeCoverage/Filter.php';
        PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'PHPUNIT');

        //require_once 'PHPUnit/Autoload.php';

        define('PHPUnit_MAIN_METHOD', 'IDE_PHPUnit_TextUI_Command::main');

    }

    /**
     * @return void
     */
    public static function load33()
    {

        if (extension_loaded('xdebug')) {
            xdebug_disable();
        }

        require_once 'PHPUnit/Util/Filter.php';

        PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

        require 'PHPUnit/TextUI/Command.php';

        define('PHPUnit_MAIN_METHOD', 'IDE_PHPUnit_TextUI_Command::main');

    }

    private static function readPharVersion($text, $versionStart, $versionEnd) {
        if ($versionEnd > $versionStart) {
            $pharVersion = substr($text, $versionStart, ($versionEnd - $versionStart));
            $prefix = "phpunit-";
            if (substr($pharVersion, 0, strlen($prefix)) === $prefix) {
                $pharVersion = substr($pharVersion, strlen($prefix));
            }
            $suffix = ".phar";
            if (substr($pharVersion,-strlen($suffix)) === $suffix) {
                $pharVersion = substr($pharVersion, 0, strlen($pharVersion)-strlen($suffix));
            }

            return $pharVersion;
        }
        return null;
    }

    public static function init() {
        if (isset($_GET["load_mode"]) && $_GET["load_mode"] == "l") {
            // load by autoload.php
            $path = $_GET['load_path'];
            if (!file_exists($path)) {
                echo "The value \$_GET['load_path'] is specified, but file doesn't exist '$path'\n";
                exit(IDE_Remote_PHPUnit_Loader::FAILURE_EXIT);
            }
            require_once $path;
        }
        else if (isset($_GET["load_mode"]) && $_GET["load_mode"] == "p") {
            // load by phar
            $path = $_GET['load_path'];
            if (!file_exists($path)) {
                echo "The value \$_GET['load_path'] is specified, but file doesn't exist '$path'\n";
                exit(IDE_Remote_PHPUnit_Loader::FAILURE_EXIT);
            }
            $phar = new Phar($path);
            $alias = $phar->getAlias();
            Phar::loadPhar($path, $alias);
            //awful hack start (but I don't know a better way to do it)
            $stub = $phar->getStub();
            $i = strpos($stub, "<?php\n");
            $stub = substr($stub, $i + 6);
            $i = strpos($stub, "Phar::mapPhar");
            $stub_prefix = substr($stub, 0, $i);

            $i = strpos($stub, "Phar::mapPhar");
            $mapEnd = strpos($stub, ")", $i);
            $pharVersion = IDE_Remote_PHPUnit_Loader::readPharVersion($stub, $i + strlen("Phar::mapPhar") + 2, $mapEnd - 1);
            if (!isset($pharVersion) ||  $pharVersion < "4.6") {
                eval($stub_prefix);
            }
            else {
                $stub_suffix = substr($stub, $mapEnd + 2);
                eval($stub_prefix . $stub_suffix);
            }
            //awful hack end
        }
        else {
            // load by include path
            IDE_Remote_PHPUnit_Loader::checkIncludePath();
            IDE_Remote_PHPUnit_Loader::detectPHPUnitVersionId();

            //load PHPUnit
            switch (IDE_Remote_PHPUnit_Loader::$PHPUnitVersionId) {
                case "33":
                case "34": {
                    IDE_Remote_PHPUnit_Loader::load33();
                    break;
                }
                case "35": {
                    IDE_Remote_PHPUnit_Loader::load35();
                    break;
                }
                case "36": {
                    IDE_Remote_PHPUnit_Loader::load36();
                    break;
                }
                default: {
                    IDE_Remote_PHPUnit_Loader::load();
                }
            }


            if (!IDE_Remote_PHPUnit_Loader::fileExistsInIncludePath("/PHPUnit")) {
                print("PHPUnit is missing from include paths.\nPlease adjust ''include_path'' variable in your ''php.ini'' configuration file.");
                exit;
            }
        }
    }

    private static function fileExistsInIncludePath($file) {
        if (file_exists($file)) {
            return realpath($file);
        }

        $paths = explode(PATH_SEPARATOR, get_include_path());

        foreach ($paths as $path) {
            $fullpath = $path . DIRECTORY_SEPARATOR . $file;

            if (file_exists($fullpath)) {
                return realpath($fullpath);
            }
        }

        return FALSE;
    }
}

IDE_Remote_PHPUnit_Loader::init();

// =================================== PHPUnit Printer
class IDE_Base_Remote_PHPUnit_TextUI_ResultPrinter extends PHPUnit_TextUI_ResultPrinter {
    /**
     * @param PHPUnit_Util_Printer $printer
     */
    function __construct($printer) {
        parent::__construct(null);
        if (!is_null($printer)) {
            $this->out = $printer->out;
            $this->outTarget = $printer->outTarget;
        }
    }

    protected function writeProgress($progress) {
        //ignore
    }
}

switch (IDE_Remote_PHPUnit_Loader::$PHPUnitVersionId) {
    case "34":
    {
        class IDE_Remote_PHPUnit_TextUI_ResultPrinter extends IDE_Base_Remote_PHPUnit_TextUI_ResultPrinter {
            public function printResult(PHPUnit_Framework_TestResult $result) {
                $this->printHeader($result->time());
                $this->printFooter($result);
            }
        }

        break;
    }
    default:
        {
        class IDE_Remote_PHPUnit_TextUI_ResultPrinter extends IDE_Base_Remote_PHPUnit_TextUI_ResultPrinter {
            public function printResult(PHPUnit_Framework_TestResult $result) {
                $this->printHeader();
                $this->printFooter($result);
            }
        }

        break;
        }
}

// =================================== PHPUnit Listener
class IDE_Remote_PHPUnit_Framework_TestListener implements PHPUnit_Framework_TestListener {
    private $previousTestName;
    private $isSummaryTestCountPrinted = false;
    /** @var PHPUnit_Util_Printer $printer */
    private $printer = false;

    /**
     * @param PHPUnit_Util_Printer $printer
     */
    function __construct($printer) {
        $this->printer = $printer;
    }

    private static function getMessage(Exception $e)
    {
        $message = "";
        if (!($e instanceof PHPUnit_Framework_Exception)) {
            if (strlen(get_class($e)) != 0) {
                $message = $message . get_class($e);
            }
            if (strlen($message) != 0 && strlen($e->getMessage()) != 0) {
                $message = $message . " : ";
            }
        }
        return $message . $e->getMessage();
    }

    private static function getDetails(Exception $e)
    {
        if (IDE_Remote_PHPUnit_Loader::$PHPUnitVersionId == "34" ||
            IDE_Remote_PHPUnit_Loader::$PHPUnitVersionId == "35" ||
            IDE_Remote_PHPUnit_Loader::$PHPUnitVersionId == "36") {
            return self::escapeValue($e->getTraceAsString());
        }
        else {
            $stackTrace = PHPUnit_Util_Filter::getFilteredStacktrace($e);

            $previous = $e->getPrevious();
            while ($previous) {
                $stackTrace .= "\nCaused by\n" .
                    PHPUnit_Framework_TestFailure::exceptionToString($previous) . "\n" .
                    PHPUnit_Util_Filter::getFilteredStacktrace($previous);
                $previous = $previous->getPrevious();
            }
            return " " . str_replace("\n", "\n ", $stackTrace);
        }
    }

    private static function getPrimitiveValueAsString($value)
    {
        if (is_null($value)) {
            return "null";
        }
        else if (is_bool($value)) {
            return $value == true ? "true" : "false";
        }
        else if (is_scalar($value)){
            return print_r($value, true);
        }
        return null;
    }

    private static function escapeValue($text) {
        $text = str_replace("|", "||", $text);
        $text = str_replace("'", "|'", $text);
        $text = str_replace("\n", "|n", $text);
        $text = str_replace("\r", "|r", $text);
        $text = str_replace("]", "|]", $text);
        $text = str_replace("[", "|[", $text);
        return $text;
    }

    private static function getFileName($className) {
        $reflectionClass = new ReflectionClass($className);
        $fileName = $reflectionClass->getFileName();
        return $fileName;
    }

    public function addWarning(PHPUnit_Framework_Test $test, PHPUnit_Framework_Warning $e, $time)
    {
        $this->addError($test, $e, $time);
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $this->printEvent("testFailed", array(
            "name" => $test->getName(),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->addError($test, $e, $time);
    }

    private function printEvent($eventName, $params = array()) {
        $this->printer->write("\n##teamcity[$eventName");
        foreach ($params as $key => $value) {
            $escapedValue = self::escapeValue($value);
            $this->printer->write(" $key='$escapedValue'");
        }
        $this->printer->write("]\n");

        @ob_flush();
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
        $params = array(
            "name" => $test->getName(),
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        );
        if ($e instanceof PHPUnit_Framework_ExpectationFailedException) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure instanceof PHPUnit_Framework_ComparisonFailure ||
                $comparisonFailure instanceof \SebastianBergmann\Comparator\ComparisonFailure) {
                $expectedString = null;
                if (method_exists($comparisonFailure, "getExpectedAsString")) {
                    $expectedString = $comparisonFailure->getExpectedAsString();
                }
                if (is_null($expectedString) || empty($expectedString)) {
                    $expectedString = self::getPrimitiveValueAsString($comparisonFailure->getExpected());
                }
                $actualString = null;
                if (method_exists($comparisonFailure, "getActualAsString")) {
                    $actualString = $comparisonFailure->getActualAsString();
                }
                if (is_null($actualString) || empty($actualString)) {
                    $actualString = self::getPrimitiveValueAsString($comparisonFailure->getActual());
                }
                if (!is_null($actualString) && !is_null($expectedString)) {
                    $params['actual'] = $actualString;
                    $params['expected'] = $expectedString;
                }
            }
        }
        $this->printEvent("testFailed", $params);
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->printIgnoredTest($test->getName(), $e);
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $testName = $test->getName();
        if ($this->previousTestName != $testName) {
            // wrap tests failed with dependency into "testStarted"/"testFinished"
            $this->startTest($test);
            $this->printIgnoredTest($testName, $e);
            $this->endTest($test, $time);
        }
        else {
            $this->printIgnoredTest($testName, $e);
        }
    }

    public function printIgnoredTest($testName, Exception $e) {
        $this->printEvent("testIgnored", array(
            "name" => $testName,
            "message" => self::getMessage($e),
            "details" => self::getDetails($e)
        ));
    }

    public function startTest(PHPUnit_Framework_Test $test) {
        $testName = $test->getName();
        $this->previousTestName = $testName;
        $params = array(
            "name" => $testName
        );
        if ($test instanceof PHPUnit_Framework_TestCase) {
            $className = get_class($test);
            $fileName = self::getFileName($className);
            $params['locationHint'] = "php_qn://$fileName::\\$className::$testName";
        }
        $this->printEvent("testStarted", $params);
    }

    public function endTest(PHPUnit_Framework_Test $test, $time) {
        $this->printEvent("testFinished", array(
            "name" => $test->getName(),
            "duration" => (int)(round($time, 2) * 1000)
        ));
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
        if (!$this->isSummaryTestCountPrinted) {
            $this->isSummaryTestCountPrinted = true;
            //print tests count
            $this->printEvent("testCount", array(
                "count" => count($suite)
            ));
        }

        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }
        $params = array(
            "name" => $suiteName,
        );
        if (class_exists($suiteName, false)) {
            $fileName = self::getFileName($suiteName);
            $params['locationHint'] = "php_qn://$fileName::\\$suiteName";
        }
        else {
            $split = preg_split("/::/", $suiteName);
            if (sizeof($split) == 2 && method_exists($split[0], $split[1])) {
                $fileName = self::getFileName($split[0]);
                $params['locationHint'] = "php_qn://$fileName::\\$suiteName";
                $params['name'] = $split[1];
            }
        }
        $this->printEvent("testSuiteStarted", $params);
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
        $suiteName = $suite->getName();
        if (empty($suiteName)) {
            return;
        }

        $params = array(
            "name" => $suiteName,
        );

        if (!class_exists($suiteName, false)) {
            $split = preg_split("/::/", $suiteName);
            if (sizeof($split) == 2 && method_exists($split[0], $split[1])) {
                $params['name'] = $split[1];
            }
        }
        $this->printEvent("testSuiteFinished", $params);
    }
}

// =================================== PHPUnit Runner
class IDE_PHPUnit_Remote_Command extends PHPUnit_TextUI_Command {

    public static function main($exit = TRUE) {
        // 'cd' to working directory
        if (isset($_GET["dir"])) {
            chdir($_GET["dir"]);
        }

        $command = new IDE_PHPUnit_Remote_Command();

        $options = array();
        if (isset($_GET["options"])) {
            $options = explode(" ", $_GET["options"]);
        }
        else if (php_sapi_name() == "cli" && isset($_SERVER['argv'])) {
            // for phpunit loading tests on teamcity
            $options = $_SERVER['argv'];
        }
        $command->run($options, $exit);
    }

    protected function handleArguments(array $argv) {
        // at first parse query parameters
        if (isset($_GET["config"])) {
            $this->arguments['configuration'] = $_GET["config"];
        }

        if (isset($_GET["groups"])) {
            $this->arguments['groups'] = $_GET["groups"];
        }

        if (isset($_GET["exclude_groups"])) {
            $this->arguments['excludeGroups'] = $_GET["exclude_groups"];
        }

        if (isset($_GET["method"])) {
            // TODO: send as 'method' already as 'filter' option
            $this->arguments['filter'] = "/::" . $_GET["method"] . "( .*)?$/";
        }

        if (isset($_GET["class"])) {
            $this->arguments['test'] = $_GET["class"];
        }

        if (isset($_GET["mode"])) {
            if ($_GET["mode"] == "d") {
                $this->arguments['test'] = $_GET["file"];
            }
            else if ($_GET["mode"] == "f") {
                $this->arguments['test'] = $_GET["file"];
                $this->arguments['testFile'] = $_GET["file"];
            }
            else {
                $this->arguments['testFile'] = $_GET["file"];
            }
        }

        // process parameters to create testSuite
        parent::handleArguments($argv);

        // set output option
        if (isset($this->arguments['printer'])) {
            $printer = $this->arguments['printer'];
        }
        else {
            $printer = null;
        }
        $printer = new IDE_Remote_PHPUnit_TextUI_ResultPrinter($printer);
        $this->arguments['printer'] = $printer;
        $this->arguments['listeners'][] = new IDE_Remote_PHPUnit_Framework_TestListener($printer);
    }
}

IDE_PHPUnit_Remote_Command::main();

