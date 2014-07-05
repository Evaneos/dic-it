<?php

namespace DICIT\Tests;

use DICIT\Container;
use DICIT\ActivatorFactory;
use DICIT\ContainerFactory;

class ContainerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \DICIT\UnknownDefinitionException
     */
    public function testContainerThrowsExceptionOnMissingServiceName()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('doLoad')
            ->will($this->returnValue(array()));

        $container = ContainerFactory::create($config);

        $container->get('UnknownService');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testContainerThrowsExceptionOnMissingDependency()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('doLoad')
            ->will($this->returnValue(array(
                'classes' => array(
                    'service' => array('class' => '\stdClass', 'props' => array('dep' => '@missing-service'))
            ))));

        $container = ContainerFactory::create($config);

        $container->get('service');
    }

    public function testContainerReturnsCorrectParameterValue()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('doLoad')
            ->will($this->returnValue(array(
                'parameters' => array(
                    'param' => 'value'
                ))));

        $container = ContainerFactory::create($config);

        $this->assertEquals('value', $container->getParameter('param'));
    }

    /**
     * @param string $name
     */
    private function getCyclicDependencies($name, $singletonForFirst = false, $singletonForOther = false)
    {
        $first = array(
            'class' => '\stdClass',
            'singleton' => $singletonForFirst,
            'props' => array(
                'cyclic' => '@' . $name . '-dependency'
            )
        );
        $second = array(
            'class' => '\stdClass',
            'singleton' => $singletonForOther,
            'props' => array(
                'cyclic' => '@' . $name
            )
        );

        return array('classes' => array($name => $first, $name . '-dependency' => $second));
    }

    public function testCyclicDependenciesDoNotOverflowWithOneSingletonInCycle()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getData'))
            ->getMockForAbstractClass();

        $first = rand(0, 1);

        $config->expects($this->any())
            ->method('load')
            ->will($this->returnValue($this->getCyclicDependencies('cyclic', $first, ! $first)));

        $container = ContainerFactory::create($config, array('deferred' => true));

        $container->get('cyclic');
    }

    public function testCyclicDependenciesDoNotOverflowWithTwoSingletonsInCycle()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getData'))
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('load')
            ->will($this->returnValue($this->getCyclicDependencies('cyclic', true, true)));

        $container = ContainerFactory::create($config, array('deferred' => true));

        $container->get('cyclic');
    }

    public function testResolvingAManuallyBoundObjectReturnsCorrectInstance()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getData'))
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('load')
            ->will($this->returnValue(array('classes' => array())));

        $container = ContainerFactory::create($config, array('deferred' => true));

        $item = new \stdClass();

        $container->bind('boundKey', $item);

        $this->assertSame($item, $container->get('boundKey'));
    }

    public function testResolvingAManuallyBoundObjectDefinitionReturnsCorrectInstance()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getData'))
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('load')
            ->will($this->returnValue(array('classes' => array())));

        $container = ContainerFactory::create($config, array('deferred' => true));

        $itemDefinition = array(
            'class' => '\stdClass',
            'props' => array(
                'dummy' => 'dummy-value'
            )
        );

        $container->bind('boundKey', $itemDefinition);

        $item = $container->get('boundKey');

        $this->assertEquals('dummy-value', $item->dummy);
    }

    /**
     * @expectedException \DICIT\IllegalTypeException
     */
    public function testAddingAParameterCallableThrowsException()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getData'))
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('load')
            ->will($this->returnValue(array('parameters' => array(), 'classes' => array())));

        $container = ContainerFactory::create($config);

        $container->setParameter('dummy.key', function() { });
    }

    /**
     * @expectedException \DICIT\IllegalTypeException
     */
    public function testAddingAParameterWithCallableInArrayThrowsException()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getData'))
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('load')
            ->will($this->returnValue(array('parameters' => array(), 'classes' => array())));

        $container = ContainerFactory::create($config);

        $container->setParameter('dummy.key', array('dummy-key1' =>'value1', 'dummy-key2' => function() {}));
    }


    /**
     * @expectedException \DICIT\IllegalTypeException
     */
    public function testAddingAParameterWithCallableInArrayMultiDimensionalThrowsException()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getData'))
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('load')
            ->will($this->returnValue(array('parameters' => array(), 'classes' => array())));

        $container = ContainerFactory::create($config);

        $container->setParameter('dummy.key', array('dummy-key1' =>'value1', "sub" => array('dummy-key2' => function() {})));
    }


    /**
     * @expectedException \DICIT\IllegalTypeException
     */
    public function testAddingAParameterWithObjectInArrayMultiDimensionalThrowsException()
    {
        $config = $this->getMockBuilder('\DICIT\Config\AbstractConfig')
            ->disableOriginalConstructor()
            ->setMethods(array('load', 'getData'))
            ->getMockForAbstractClass();

        $config->expects($this->any())
            ->method('load')
            ->will($this->returnValue(array('parameters' => array(), 'classes' => array())));

        $container = ContainerFactory::create($config);

        $container->setParameter('dummy.key', array('dummy-key1' =>'value1', "sub" => array('dummy-key2' => new \stdClass())));
    }

    public function testGettingMultiDimensionalParameterReturnCorrectValue()
    {
        $yml = <<<YML
parameters :
    dummy :
        key : dummy-value
YML;

        $container = ContainerFactory::createFromInlineYaml($yml);

        $value = $container->getParameter('dummy.key');

        $this->assertSame('dummy-value', $value);
    }

    public function testAddingAParameterInMultiDimensionialReturnSame()
    {
        $yml = <<<YML
parameters :
    dummy :
        key : dummy-value
YML;

        $container = ContainerFactory::createFromInlineYaml($yml);

        $container->setParameter('dummy.key2', 'dummy-value2');
        $value = $container->getParameter('dummy.key2');
        $this->assertSame('dummy-value2', $value);
    }

    public function testAddingAnArrayParameterInMultiDimensionialReturnSame()
    {
        $yml = <<<YML
parameters :
YML;

        $container = ContainerFactory::createFromInlineYaml($yml);
        $dbConfig = array("host" => "127.0.0.1", "port" => 5432);
        $container->setParameter('dummy', array('db' => $dbConfig));
        $host = $container->getParameter('dummy.db.host');
        $port = $container->getParameter('dummy.db.port');
        $db = $container->getParameter('dummy.db');
        $this->assertSame('127.0.0.1', $host);
        $this->assertSame(5432, $port);
        $this->assertSame($dbConfig, $db);
    }

    public function testAddingParameterWontEraseCollateralData()
    {
        $yml = <<<YML
parameters :
    dummy :
        key : dummy-value
YML;

        $container = ContainerFactory::createFromInlineYaml($yml);
        $container->setParameter('dummy', array('db' => array("host" => "127.0.0.1", "port" => 5432)));
        $this->assertSame('dummy-value', $container->getParameter('dummy.key'));
    }
}