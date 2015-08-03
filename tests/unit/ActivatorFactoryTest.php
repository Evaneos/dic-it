<?php
namespace DICIT\Tests;

use DICIT\ActivatorFactoryPrebuilt;
use PHPUnit_Framework_TestCase;

class ActivatorFactoryTest extends PHPUnit_Framework_TestCase
{

    public function getInvalidServiceConfigurations()
    {
        return array(
            array(array()),
            array(array('class' => '\DummyClass', 'builder' => '\invalidBuilderDefinition'))
        );
    }

    /**
     * @dataProvider getInvalidServiceConfigurations
     * @expectedException \DICIT\Exception\UnbuildableServiceException
     */
    public function testGetActivatorThrowsExceptionForInvalidConfigurations($serviceConfig)
    {
        $factory = new ActivatorFactoryPrebuilt();

        $factory->getActivator('myService', $serviceConfig);
    }

    public function testGetActivatorWithStaticBuilderConfigReturnsStaticActivator()
    {
        $serviceConfig = array('class' => '\DummyClass', 'builder' => '\DummyBuilder::dummyFactoryMethod');

        $factory = new ActivatorFactoryPrebuilt();

        $this->assertInstanceOf('\DICIT\Activators\StaticInvocationActivator',
            $factory->getActivator('myService', $serviceConfig));
    }

    public function testGetActivatorWithInstanceBuilderConfigReturnsInstanceActivator()
    {
        $serviceConfig = array('class' => '\DummyClass', 'builder' => '@DummyBuilder->dummyFactoryMethod');

        $factory = new ActivatorFactoryPrebuilt();

        $this->assertInstanceOf('\DICIT\Activators\InstanceInvocationActivator',
            $factory->getActivator('myService', $serviceConfig));
    }

    public function testGetActivatorWithRemoteConfigReturnsRemoteActivator()
    {
        $serviceConfig = array('class' => '\DummyClass', 'remote' => array());

        $factory = new ActivatorFactoryPrebuilt();

        $this->assertInstanceOf('\DICIT\Activators\RemoteActivator',
            $factory->getActivator('myService', $serviceConfig));
    }
}
