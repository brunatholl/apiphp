<?php

IDE_PHPUnit_Loader::checkIncludePath();
IDE_PHPUnit_Loader::detectPHPUnitVersionId();

//load PHPUnit
switch (IDE_PHPUnit_Loader::$PHPUnitVersionId) {
    case "33":
    case "34": {
        IDE_PHPUnit_Loader::load33();
        break;
    }
    case "35": {
        IDE_PHPUnit_Loader::load35();
        break;
    }
    case "36": {
        IDE_PHPUnit_Loader::load36();
        break;
    }
}

class IDE_PHPUnit_Loader
{

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
        $PHPUnitParentDirectory = self::getPHPUnitParentDirectory();
        if (is_null($PHPUnitParentDirectory)) {
            echo "Cannot find PHPUnit in include path (" . ini_get('include_path') . ")";
            exit(IDE_PHPUnit_Loader::FAILURE_EXIT);
        }
    }

    /**
     * @return null | string
     */
    private static function getPHPUnitParentDirectory()
    {
        $pathArray = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($pathArray as $path)
        {
            if (file_exists($path . DIRECTORY_SEPARATOR . 'PHPUnit/')) {
                return $path;
            }
        }
        return null;
    }

    /**
     * @return void
     */
    public static function detectPHPUnitVersionId()
    {
        require_once 'PHPUnit/Runner/Version.php';

        $PHPUnitVersion = PHPUnit_Runner_Version::id();

        if ($PHPUnitVersion === "@package_version@") {

            self::$PHPUnitVersionId = "36";

        }
        else if (version_compare($PHPUnitVersion, '3.6.0') >= 0) {

            self::$PHPUnitVersionId = "36";
        }
        else if (version_compare($PHPUnitVersion, '3.5.0') >= 0) {

            self::$PHPUnitVersionId = "35";
        }
        else if (version_compare($PHPUnitVersion, '3.4.0') >= 0) {

            self::$PHPUnitVersionId = "34";
        }
        else if (version_compare($PHPUnitVersion, '3.3.0') >= 0) {

            self::$PHPUnitVersionId = "33";
        }
        else if (version_compare($PHPUnitVersion, '3.3.0') < 0) {

            echo "unsupported PHPUnit version:  $PHPUnitVersion";
            exit(IDE_PHPUnit_Loader::FAILURE_EXIT);
        }
    }


    /**
     * @return void
     */
    public static function load36()
    {
        define('PHPUnit_MAIN_METHOD', 'IDE_PHPUnit_TextUI_Command::main');

        require 'PHPUnit/Autoload.php';

    }


    /**
     * @return void
     */
    public static function load35()
    {

        require_once 'PHP/CodeCoverage/Filter.php';
        PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'PHPUNIT');

        require_once 'PHPUnit/Autoload.php';

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

}

function fileExistsInIncludePath($file) {
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

if (!fileExistsInIncludePath("/PHPUnit")) {
    print("PHPUnit is missing from include paths.\nPlease adjust ''include_path'' variable in your ''php.ini'' configuration file.");
    exit;
}

$global_phpunit_request_start = true;

function getErrorMessageFromException(Exception $e) {
    $message = "";
    if (strlen(get_class($e)) != 0) {
        $message = $message . get_class($e);
    }
    if (strlen($message) != 0 && strlen($e->getMessage()) != 0) {
        $message = $message . " : ";
    }
    $message = $message . $e->getMessage();

    return $message;
}

function getLocation($_path) {
    return $_path;
}


function getErrorMessage($errno) {
    switch ($errno) {
        case E_ERROR:
            return "E_ERROR";
        case E_WARNING:
            return "E_WARNING";
        case E_PARSE:
            return "E_PARSE";
        case E_NOTICE:
            return "E_NOTICE";
        case E_CORE_ERROR:
            return "E_CORE_ERROR";
        case E_CORE_WARNING:
            return "E_CORE_WARNING";
        case E_COMPILE_ERROR:
            return "E_COMPILE_ERROR";
        case E_COMPILE_WARNING:
            return "E_COMPILE_WARNING";
        case E_USER_ERROR:
            return "E_USER_ERROR";
        case E_USER_WARNING:
            return "E_USER_WARNING";
        case E_USER_NOTICE:
            return "E_USER_NOTICE";
        case E_ALL:
            return "E_ALL";
        case E_STRICT:
            return "E_STRICT";
        case E_RECOVERABLE_ERROR:
            return "E_RECOVERABLE_ERROR";
        case E_DEPRECATED:
            return "E_DEPRECATED";
        /*
              case E_USER_DEPRECATED:
                  return "E_USER_DEPRECATED";
        */
        default:
            return "Unknown error type";
    }
}

function composeOneLineTraceMessage($index, $fileName, $line, $functionName, $args) {
    $result = "#" . $index . " ";
    if (!empty($fileName)) {
        $result .= $fileName;
        if ($line) {
            $result .= "(" . $line . ")";
        }
        if (!empty($functionName)) {
            $result .= ": " . $functionName . "()";
/*            for ($i = 0; $i < count($args); $i++) {
                $type = gettype($args[$i]);
                if ($type == "object") {
                    $type = get_class($args[$i]);
                }
                $result .= $type;
                if ($i + 1 < count($args)) {
                    $result .= ", ";
                }
            }
            $result .= ")";*/
        }
    }
    $result .= "\n";
    return $result;
}

// #0 /home/conf/limb_rep/limb/debug.php(8): zoo()
function getTraceMessage(array $trace, $filename) {
    $result = "";
    $index = 0;

    foreach ($trace as $traceline) {
        if (empty($traceline["file"])) {
            continue;
        }
        $result .= composeOneLineTraceMessage($index,
                                              isset($traceline["file"]) ? $traceline["file"] : null,
                                              isset($traceline["line"]) ? $traceline["line"] : null,
                                              isset($traceline["function"]) ? $traceline["function"] : null,
                                              isset($traceline["args"]) ? $traceline["args"] : null);
        $index++;
    }
    return $result;
}

function myExceptionHandler(Exception $e) {
    $output = getErrorMessageFromException($e) . "\n" . getTraceMessage($e->getTrace(), getLocation($e->getFile()));
    flush();
}

$doConvertErrorToExceptions = false;
// $user_error_reporting_level = 0;

function myErrorHandler_DirectReport($errno, $errstr, $errfile, $errline) {
    global $doConvertErrorToExceptions;

    // $errstr = $errstr . " FH";

    if (/*!$doConvertErrorToExceptions && */!($errno & error_reporting())) {
//         print("Returned TRUE from error_handler\n");
        return FALSE;
    }
//    print("error_handler prints error\n");

    if (version_compare(PHP_VERSION, '5.2.5', '>=')) {
        $trace = debug_backtrace(FALSE);
    }
    else {
        $trace = debug_backtrace();
    }

    array_shift($trace);

    foreach ($trace as $frame) {
        if ($frame['function'] == '__toString') {
            return FALSE;
        }
    }

    if ($doConvertErrorToExceptions) {
        $errstr = getErrorMessage($errno) . ": " .
            $errstr . "\n" . composeOneLineTraceMessage(0, getLocation($errfile), $errline, null, null);

        if ($errno == E_NOTICE || $errno == E_STRICT) {
            if (PHPUnit_Framework_Error_Notice::$enabled === TRUE) {
                throw new PHPUnit_Framework_Error_Notice($errstr, $errno, $errfile, $errline, null);
            }
        } else {
            if ($errno == E_WARNING) {
                if (PHPUnit_Framework_Error_Warning::$enabled === TRUE) {
                    throw new PHPUnit_Framework_Error_Warning($errstr, $errno, $errfile, $errline, null);
                }
            } else {
                throw new PHPUnit_Framework_Error($errstr, $errno, $errfile, $errline, null);
            }
        }

    }
        $errstr = getErrorMessage($errno) . ": " .
                  $errstr;

        print($errstr . "\n" . getTraceMessage($trace, getLocation($errfile)));
        return TRUE;
}

function myFatalErrorHandler() {
    global $global_phpunit_request_start;
    if (isset($global_phpunit_request_start) && $global_phpunit_request_start) {
        print("PHPUnit is not configured properly");
        flush();
        die();
    }
    $last_error = error_get_last();
    if (!empty($last_error) && ($last_error['type'] & error_reporting())) {
        print(getErrorMessage($last_error['type']) . ": " .
              $last_error['message'] . "\n" .
              composeOneLineTraceMessage(0, $last_error['file'], $last_error['line'], null, null));
    }
}

$isPhpUnit3_5 = fileExistsInIncludePath('PHPUnit/Autoload.php');

// set_exception_handler('myExceptionHandler');

// E_WARNING is responsible for expected PHPUnit_Framework_Error
// $user_error_reporting_level = error_reporting(E_ALL & (~(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_STRICT)));
// error suppression is added because of xdebug, which prints errors in html format, when run remotely
$my_initial_error_handler = set_error_handler('myErrorHandler_DirectReport', E_ALL | E_STRICT/*, $my_direct_error_reporting_level*/);

register_shutdown_function('myFatalErrorHandler');

$global_phpunit_request_start = false;

function escape($text) {
    $text = str_replace("|", "||", $text);
    $text = str_replace("'", "|'", $text);
    $text = str_replace("\n", "|n", $text);
    $text = str_replace("\r", "|r", $text);
    $text = str_replace("]", "|]", $text);

    return $text;
}

function traceCommand($command, $param1Name, $param1Value,
                      $param2Name = null, $param2Value = null,
                      $param3Name = null, $param3Value = null) {
    $line = "\n##teamcity[" . $command . " " . $param1Name . "='" . escape($param1Value) . "'";
    if ($param2Name != null) {
        $line = $line . " " . $param2Name . "='" . escape($param2Value) . "'";
    }
    if ($param3Name != null) {
        $line = $line . " " . $param3Name . "='" . escape($param3Value) . "'";
    }

    $line = $line . "]\n";
    return $line;
}

/**
 * Check if string is null or empty
 * @param $str
 * @return bool
 */
function isEmpty($str) {
    return $str == "" || !isset($str);
}

class SimpleTestListener extends PHPUnit_Util_Printer implements PHPUnit_Framework_TestListener {
    var $LOCATION_PROTOCOL;

    var $myfilename;

    var $depth;

    public function __construct($filename = "") {
        $this->LOCATION_PROTOCOL = "php_qn://";
        $this->myfilename = getLocation((string)$filename);
        $this->depth = 0;
    }

    public function flush() {
    }

    public function incrementalFlush() {
    }

    public function write($buffer) {
    }

    public function getAutoFlush() {
        return $this->autoFlush;
    }

    public function setAutoFlush($autoFlush) {
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
        print(traceCommand("testFailed", "name", $test->getName(), "message", getErrorMessageFromException($e),
                           "details", getTraceMessage($e->getTrace(), $this->myfilename)));
        flush();
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
        if ($test instanceof PHPUnit_Framework_Warning) {
            return;
        }
        print(traceCommand("testFailed", "name", $test->getName(), "message", getErrorMessageFromException($e),
                           "details", /*getPrettyTrace($e->getTraceAsString())*/
                           getTraceMessage($e->getTrace(), $this->myfilename)));
        flush();
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        if (!is_null($e)) {
            print(traceCommand("testIgnored", "name", $test->getName(), "message", getErrorMessageFromException($e),
                               "details", getTraceMessage($e->getTrace(), $this->myfilename)/*,
                "locationHint", "php_qn://" . $this->myfilename . "::" . $test->toString()*/));
        }
        else {
            print(traceCommand("testIgnored", "name", $test->getName(), "message", ""));
        }
        // print(traceCommand("testIgnored", "name", $test->getName()));
        flush();
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        print(traceCommand("testIgnored", "name", $test->getName(), "message", getErrorMessageFromException($e)));
        flush();
    }

    public function startTest(PHPUnit_Framework_Test $test) {
        if ($test instanceof PHPUnit_Framework_Warning) {
            $pieces = explode('"', $test->getMessage());
            if (count($pieces) >= 3) {
                $testName = $pieces[1];
                $shownTestName = "Warning (" . $testName . ")";

                print(traceCommand("testStarted", "name", $shownTestName, "locationHint",
                                   "php_qn://" . $this->myfilename . "::::" . $testName));
                print(traceCommand("testIgnored", "name", $shownTestName, "message", $test->getMessage()));
                print(traceCommand("testFinished", "name", $shownTestName, "duration", 0));
                return;
            }
        }
        print(traceCommand("testStarted", "name", $test->getName(), "locationHint",
                           "php_qn://" . $this->myfilename . "::" . $test->toString()));
        flush();
    }

    public function endTest(PHPUnit_Framework_Test $test, $time) {
        print(traceCommand("testFinished", "name", $test->getName(), "duration", (int)(round($time, 2) * 1000)));
        flush();
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
        $this->depth++;
        $name = $suite->getName();
        if ($this->depth == 1 && isEmpty($name)) {
            // don't print root test suite element if name is empty
            return;
        }

        if (isEmpty($name)) {
            $name = "<no name>";
        }
        $location = $this->LOCATION_PROTOCOL . $this->myfilename . "::" . $suite->toString();
        print(traceCommand("testSuiteStarted", "name", $name . " (" . basename($this->myfilename) . ")",
            "locationHint", $location));
        flush();
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
        $this->depth--;
        $name = $suite->getName();
        if ($this->depth == 0 && isEmpty($name)) {
            // don't print root test suite element if name is empty
            return;
        }

        if (isEmpty($name)) {
            $name = "<no name>";
        }
        print(traceCommand("testSuiteFinished", "name", $name . " (" . basename($this->myfilename) . ")"));
        flush();
    }
}

class MyTestRunner {
    private static function runTest(PHPUnit_Framework_TestSuite $test, $filename, $arguments) {
        global $doConvertErrorToExceptions;
        $doConvertErrorToExceptions = true;
        try {
            if (isset($arguments['loader'])) {
                $runner = new PHPUnit_TextUI_TestRunner($arguments['loader']);
            }
            else {
                $runner = new PHPUnit_TextUI_TestRunner();
            }
            $arguments['printer'] = new SimpleTestListener(self::relativePath(realpath($filename)));
            $result = $runner->doRun($test, $arguments);
        } catch (PHPUnit_Framework_Error $e) {
            myExceptionHandler($e);

        } catch (Exception $e) {
            myExceptionHandler($e);
        }
        $doConvertErrorToExceptions = false;
    }

    private static function relativePath($path, $from = __FILE__ )
    {
        $path = explode(DIRECTORY_SEPARATOR, $path);
        $from = explode(DIRECTORY_SEPARATOR, dirname($from.'.'));
        $common = array_intersect_assoc($path, $from);

        $base = array('.');
        if ( $pre_fill = count( array_diff_assoc($from, $common) ) ) {
            $base = array_fill(0, $pre_fill, '..');
        }
        $path = array_merge( $base, array_diff_assoc($path, $common) );
        return implode(DIRECTORY_SEPARATOR, $path);
    }

    public static function collectTestsFromFile($filename) {
        PHPUnit_Util_Class::collectStart();
        PHPUnit_Util_Fileloader::checkAndLoad($filename, FALSE);
        $newClasses = PHPUnit_Util_Class::collectEnd();
        $baseName = str_replace('.php', '', basename($filename));

        foreach ($newClasses as $className) {
            if (substr($className, 0 - strlen($baseName)) == $baseName) {
                $newClasses = array($className);
                break;
            }
        }
        $tests = array();
        // of PHPUnit_Framework_Test
        foreach ($newClasses as $className) {
            $class = new ReflectionClass($className);

            if (!$class->isAbstract()) {
                if ($class->hasMethod(PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME)) {
                    $method = $class->getMethod(
                        PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME
                    );

                    if ($method->isStatic()) {
                        $newTest = $method->invoke(NULL, $className);
                        // of type PHPUnit_Framework_Test
                        $tests[] = $newTest;
                    }
                }

                else {
                    if ($class->implementsInterface('PHPUnit_Framework_Test')) {
                        $tests[] = new PHPUnit_Framework_TestSuite($class);
                    }
                }
            }
        }
        return $tests;
    }

    /*
    --foo --bar=baz
      ["foo"]   => true
      ["bar"]   => "baz"

    test.php -abc
      ["a"]     => true
      ["b"]     => true
      ["c"]     => true

    test.php arg1 arg2 arg3
      [0]       => "arg1"
      [1]       => "arg2"
      [2]       => "arg3"
    */
    private static function parseLocalArguments() {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        $out = array();
        foreach ($argv as $arg){
            if (substr($arg,0,2) == '--'){
                $eqPos = strpos($arg,'=');
                if ($eqPos === false){
                    $key = substr($arg,2);
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                } else {
                    $key = substr($arg,2,$eqPos-2);
                    $out[$key] = substr($arg,$eqPos+1);
                }
            } else if (substr($arg,0,1) == '-'){
                if (substr($arg,2,1) == '='){
                    $key = substr($arg,1,1);
                    $out[$key] = substr($arg,3);
                } else {
                    $chars = str_split(substr($arg,1));
                    foreach ($chars as $char){
                        $key = $char;
                        $out[$key] = isset($out[$key]) ? $out[$key] : true;
                    }
                }
            } else {
                $out[] = $arg;
            }
        }
        return $out;
    }

    private static function parseRemoteArguments() {
        $out = array();
        if (isset($_GET["config"])) {
            $out["config"] = $_GET["config"];
            $out["mode"] = "x";
        }

        if (isset($_GET["groups"])) {
            $out["groups"] = $_GET["groups"];
        }
        if (isset($_GET["exclude_groups"])) {
            $out["excludeGroups"] = $_GET["exclude_groups"];
        }
        if (isset($_GET["mode"])) {
            $out["mode"] = $_GET["mode"];
        }
        if (isset($_GET["file"])) {
            $out["file"] = $_GET["file"];
            if (isset($_GET["class"])) {
                $out["class"] = $_GET["class"];
                if (isset($_GET["method"])) {
                    $out["method"] = $_GET["method"];
                }
            }
        }

        return $out;
    }

    private static function parseArguments() {
        $isLocal = !isset($_SERVER['HTTP_USER_AGENT']);
        return $isLocal ? self::parseLocalArguments() : self::parseRemoteArguments();
    }

    private static function check_loader_exists($file) {
        if (function_exists('stream_resolve_include_path')) {
            // PHP 5 >= 5.3.2 (@see http://php.net/manual/ru/function.stream-resolve-include-path.php)
            return stream_resolve_include_path($file);
        }
        return PHPUnit_Util_Filesystem::fileExistsInIncludePath($file);
    }

    private static function handleLoader($loaderClass, $loaderFile = '')
    {
        if (!class_exists($loaderClass, FALSE)) {
            if ($loaderFile == '') {
                $loaderFile = PHPUnit_Util_Filesystem::classNameToFilename(
                    $loaderClass
                );
            }

            $loaderFile = self::check_loader_exists(
                $loaderFile
            );

            if ($loaderFile !== FALSE) {
                require $loaderFile;
            }
        }

        if (class_exists($loaderClass, FALSE)) {
            $class = new ReflectionClass($loaderClass);

            if ($class->implementsInterface('PHPUnit_Runner_TestSuiteLoader') &&
                $class->isInstantiable()
            ) {
                $loader = $class->newInstance();
            }
        }

        if (!isset($loader)) {
            PHPUnit_TextUI_TestRunner::showError(
                sprintf(
                    'Could not use "%s" as loader.',

                    $loaderClass
                )
            );
        }

        return $loader;
    }

    public static function main() {
        $arguments = array(
            'listGroups' => FALSE,
            'loader' => NULL,
            'useDefaultConfiguration' => TRUE
        );

        $loader = NULL;
        // $startPos = 1;
        $canCountTest = true;

        if (isset($_GET["dir"])) {
            chdir($_GET["dir"]);
        }
        $args = self::parseArguments();
        if (isset($args["config"])) {
            // check if configuration specified
            $canCountTest = false;
            $arguments['configuration'] = $args["config"];
            // $startPos = 3;
            $configuration = PHPUnit_Util_Configuration::getInstance($args["config"]);
            $phpunit = $configuration->getPHPUnitConfiguration();

            if (isset($phpunit['testSuiteLoaderClass'])) {
                if (isset($phpunit['testSuiteLoaderFile'])) {
                    $file = $phpunit['testSuiteLoaderFile'];
                }
                else {
                    $file = '';
                }

                $loader = self::handleLoader($phpunit['testSuiteLoaderClass'], $file);
                $arguments['loader'] = $loader;
            }

            $configuration->handlePHPConfiguration();

            $phpunitConfiguration = $configuration->getPHPUnitConfiguration();

            if (isset($phpunitConfiguration['bootstrap'])) {
                PHPUnit_Util_Fileloader::load($phpunitConfiguration['bootstrap']);
            }

            $browsers = $configuration->getSeleniumBrowserConfiguration();

            if (!empty($browsers)) {
                require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
                PHPUnit_Extensions_SeleniumTestCase::$browsers = $browsers;
            }
        }

        if (isset($args['groups'])) {
            $arguments['groups'] = $args['groups'];
        }
        if (isset($args['excludeGroups'])) {
            $arguments['excludeGroups'] = $args['excludeGroups'];
        }

        if ($args["mode"] == "c") {
            self::runTestByClass($args, $loader, $canCountTest, $arguments);
        }
        else if ($args["mode"] == "d") {
            self::runTestByDirectory($args, $canCountTest, $arguments);
        }
        else if ($args["mode"] == 'f') {
            self::runTestByFile($args, $canCountTest, $arguments);
        }
        else if ($args["mode"] == 'm') {
            self::runTestByMethod($args, $canCountTest, $arguments);
        }
        else if ($args["mode"] == 'x') {
            self::runTestByXml($configuration, $arguments);
        }
    }

    /**
     * @param $args
     * @param $loader
     * @param $canCountTest
     * @param $arguments
     */
    public static function runTestByClass($args, $loader, $canCountTest, $arguments) {
        $suiteClassName = $args["class"];
        $suiteClassFile = $args["file"];
        try {
            // $testClass = ();

            if ($loader == NULL) {
                $loader = new PHPUnit_Runner_StandardTestSuiteLoader;
            }
            $testClass = $loader->load($suiteClassName, $suiteClassFile, FALSE);
        }
        catch (Exception $e) {
            myExceptionHandler($e);
            return;
        }
        try {
            // if class is a suite
            $suiteMethod = $testClass->getMethod('suite');

            if ($suiteMethod->isAbstract() ||
                !$suiteMethod->isPublic() ||
                !$suiteMethod->isStatic()
            ) {
                return;
            }

            try {
                // ?? suite does not have testName argument
                $test = $suiteMethod->invoke(NULL, $testClass->getName());
                $test->setName($suiteClassName);
                if ($canCountTest) {
                    print(traceCommand("testCount", "count", (string)sizeof($test)));
                }
                self::runTest($test, $suiteClassFile, $arguments);
            }
            catch (ReflectionException $e) {
                myExceptionHandler($e);
                return;
            }
        }
        catch (ReflectionException $e) {
            $test = new PHPUnit_Framework_TestSuite($testClass);
            if ($canCountTest) {
                print(traceCommand("testCount", "count", (string)sizeof($test)));
            }
            self::runTest($test, $suiteClassFile, $arguments);
        }
    }

    /**
     * @param $args
     * @param $canCountTest
     * @param $arguments
     */
    public static function runTestByDirectory($args, $canCountTest, $arguments) {
        // if run directory
        $suiteDirName = $args["file"];
        if (is_dir($suiteDirName) && !is_file($suiteDirName . '.php')) {
            if (class_exists("PHPUnit_Runner_IncludePathTestCollector")) {
                $testCollector = new PHPUnit_Runner_IncludePathTestCollector(array($suiteDirName));
                $filenames = $testCollector->collectTests();
            }
            else {
                $facade = new File_Iterator_Facade;
                $filenames = $facade->getFilesAsArray(
                    $suiteDirName, array('Test.php', '.phpt')
                );
            }

            $number = 0;
            $alltests = array();
            foreach ($filenames as $filename) {
                $tests = self::collectTestsFromFile($filename);

                foreach ($tests as $currenttest) {
                    $number += sizeof($currenttest);
                    $alltests[] = $currenttest;
                    $alltests[] = $filename;
                }
            }
            if ($canCountTest) {
                print(traceCommand("testCount", "count", (string)$number));
            }
            for ($i = 0; $i < count($alltests); $i += 2) {
                self::runTest($alltests[$i], $alltests[$i + 1], $arguments);
            }
            return;
        }
    }

    /**
     * @param $args
     * @param $canCountTest
     * @param $arguments
     */
    public static function runTestByFile($args, $canCountTest, $arguments) {
        // if run all in file
        $filename = $args["file"];
        $tests = self::collectTestsFromFile($filename);

        $test = new PHPUnit_Framework_TestSuite();
        $number = 0;
        foreach ($tests as $currenttest) {
            if ($tests) {
                $test->addTest($currenttest);
                $number += sizeof($currenttest);
            }
        }
        if ($canCountTest) {
            print(traceCommand("testCount", "count", $number));
        }

        foreach ($tests as $currentTest) {
            self::runTest($currentTest, $filename, $arguments);
        }
        return;
    }

    /**
     * @param $args
     * @param $canCountTest
     * @param $arguments
     */
    public static function runTestByMethod($args, $canCountTest, $arguments) {
        $suiteMethodName = $args["method"];
        $suiteClassName = $args["class"];
        $suiteClassFile = $args["file"];
        try {
            $testClass = (new PHPUnit_Runner_StandardTestSuiteLoader);
            $testClass = $testClass->load($suiteClassName, $suiteClassFile, FALSE);
        }
        catch (Exception $e) {
            myExceptionHandler($e);
            return;
        }
        try {
            // if class is a suite
            $suiteMethod = $testClass->getMethod($suiteMethodName);

            if ($suiteMethodName == 'suite') {
                if (($suiteMethod->isAbstract() ||
                    !$suiteMethod->isPublic() ||
                    !$suiteMethod->isStatic())
                ) {
                    return;
                }

                try {
                    $test = $suiteMethod->invoke(NULL, $testClass->getName());

                    $test->setName($suiteClassName);
                    if ($canCountTest) {
                        print(traceCommand("testCount", "count", (string)sizeof($test)));
                    }
                    self::runTest($test, $suiteClassFile, $arguments);
                }
                catch (ReflectionException $e) {
                    myExceptionHandler($e);
                    return;
                }
            }
            else {
                $test = PHPUnit_Framework_TestSuite::createTest($testClass, $suiteMethodName);
                $testSuite = new PHPUnit_Framework_TestSuite();
                $testSuite->addTest($test);
                $testSuite->setName($suiteClassName);
                if ($canCountTest) {
                    print(traceCommand("testCount", "count", (string)sizeof($test)));
                }
                self::runTest($testSuite, $suiteClassFile, $arguments);
            }
        }
        catch (ReflectionException $e) {
            myExceptionHandler($e);
            return;
        }
    }

    /**
     * @param $configuration
     * @param $arguments
     */
    public static function runTestByXml($configuration, $arguments) {
        $testSuite = $configuration->getTestSuiteConfiguration();
        self::runTest($testSuite, "", $arguments);
    }
}

// try {

MyTestRunner::main();
/*}
catch (Exception $e) {
    myExceptionHandler($e);
}*/

// restore_exception_handler();
?>
