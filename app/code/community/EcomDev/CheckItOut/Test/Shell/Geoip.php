<?php

class EcomDev_CheckItOut_Test_Shell_Geoip extends EcomDev_PHPUnit_Test_Case
{
    /**
     * CheckItOut shell object
     *
     * @var EcomDev_CheckItOut_Shell|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_shell = null;

    /**
     * Load required classes for test
     *
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        // Include shell script if class not exists
        if (!class_exists('EcomDev_CheckItOut_Shell', false)) {
            require_once Mage::getBaseDir() . DS . 'shell' . DS . 'checkitout-geoip.php';
        }
    }

    protected function setUp()
    {
        parent::setUp();
        $this->_shell = $this->getMockBuilder('EcomDev_CheckItOut_Shell')
                ->disableOriginalConstructor()
                ->setMethods(null)
                ->getMock();

        $this->_logger = new Zend_Log();
    }

    /**
     * Test that modified parse args functionality is working correctly
     *
     * @dataProvider dataProvider
     */
    public function testParseArgs($dataSet, $args)
    {
        $oldArgs = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
        $_SERVER['argv'] = $args;
        EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_shell, '_parseArgs');
        $_SERVER['argv'] = $oldArgs;

        $this->assertAttributeSame(
            $this->expected($dataSet)->getAction(), '_action', $this->_shell
        );

        $this->assertAttributeSame(
            $this->expected($dataSet)->getArgs(), '_args', $this->_shell
        );
    }

    /**
     * Test for retrieving argument by map
     *
     * @param $dataSet
     * @param $args
     * @param $arg
     * @param $defaultVal
     * @dataProvider dataProvider
     */
    public function testGetArg($dataSet, $args, $arg, $defaultVal = false)
    {
        $oldArgs = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
        $_SERVER['argv'] = $args;
        EcomDev_Utils_Reflection::invokeRestrictedMethod($this->_shell, '_parseArgs');
        $_SERVER['argv'] = $oldArgs;

        $this->assertSame(
            $this->expected($dataSet)->getData($arg),
            $this->_shell->getArg($arg, $defaultVal)
        );
    }

    /**
     * Asserts output of the script
     *
     * @param $expected
     */
    protected function assertOutput($expected)
    {

    }
}