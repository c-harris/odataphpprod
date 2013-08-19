<?php

namespace UnitTests\POData\Configuration;

use POData\Configuration\DataServiceConfiguration;
use POData\Configuration\EntitySetRights;
use POData\Configuration\DataServiceProtocolVersion;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\IDataServiceMetadataProvider;

use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;

class DataServiceConfigurationTest extends \PHPUnit_Framework_TestCase
{
	/** @var  IDataServiceMetadataProvider */
    private $_northWindMetadata;

	/** @var  DataServiceConfiguration */
    private $_dataServiceConfiguration;
    
    protected function setUp()
    {
        $this->_northWindMetadata = NorthWindMetadata::Create();
        $this->_dataServiceConfiguration = new DataServiceConfiguration($this->_northWindMetadata);
    }

    public function testConfiguration1() 
    {

        try {
            $this->_dataServiceConfiguration->setMaxExpandCount(-123);
            $this->fail('An expected InvalidArgumentException for \'non-negative parameter\' was not thrown for month');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringEndsWith('should be non-negative, negative value \'-123\' passed', $exception->getMessage());
        }



        try {
            $this->_dataServiceConfiguration->setMaxExpandDepth('ABCS');
            $this->fail('An expected InvalidArgumentException for \'non-integer parameter\' was not thrown for month');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringEndsWith('should be integer, non-integer value \'ABCS\' passed', $exception->getMessage());
        }


        $this->assertEquals($this->_dataServiceConfiguration->getMaxExpandCount(), PHP_INT_MAX);
        $this->assertEquals($this->_dataServiceConfiguration->getMaxExpandDepth(), PHP_INT_MAX);


        $this->_dataServiceConfiguration->setMaxExpandCount(6);
        $this->_dataServiceConfiguration->setMaxExpandDepth(10);
        $this->assertEquals($this->_dataServiceConfiguration->getMaxExpandCount(), 6);
        $this->assertEquals($this->_dataServiceConfiguration->getMaxExpandDepth(), 10);

    }

    public function testConfiguration2()
    {

        $this->assertEquals($this->_dataServiceConfiguration->getMaxResultsPerCollection(), PHP_INT_MAX);
        $this->_dataServiceConfiguration->setMaxResultsPerCollection(10);

        try {
            $this->_dataServiceConfiguration->setEntitySetPageSize('Customers', 5);
            $this->fail('An expected InvalidOperationException for \'page size and max result per collection mutual exclusion\' was not thrown for month');
        } catch (InvalidOperationException $exception) {

            $this->assertStringEndsWith('mutually exclusive with the specification of \'maximum result per collection\' in configuration', $exception->getMessage());
        }


        $this->assertEquals($this->_dataServiceConfiguration->getMaxResultsPerCollection(), 10);

    }

    public function testConfiguration3()
    {

        $customersResourceSet = $this->_northWindMetadata->resolveResourceSet('Customers');
        $this->assertNotNull($customersResourceSet);
        $this->assertEquals($this->_dataServiceConfiguration->getEntitySetPageSize($customersResourceSet), 0);

        $this->_dataServiceConfiguration->setEntitySetPageSize('Customers', 5);
        $this->assertEquals($this->_dataServiceConfiguration->getEntitySetPageSize($customersResourceSet), 5);

        $this->_dataServiceConfiguration->setEntitySetPageSize('*', 4);
        $ordersResourceSet = $this->_northWindMetadata->resolveResourceSet('Orders');
        $this->assertNotNull($ordersResourceSet);
        $this->assertEquals($this->_dataServiceConfiguration->getEntitySetPageSize($ordersResourceSet), 4);
        $this->assertEquals($this->_dataServiceConfiguration->getEntitySetPageSize($customersResourceSet), 5);



        try {
            $this->_dataServiceConfiguration->setEntitySetPageSize('NonExistEntitySet', 7);
            $this->fail('An expected InvalidArgumentException for \'non-exist entity set name\' was not thrown for month');
        } catch(\InvalidArgumentException $exception) {
            $this->AssertEquals('The given name \'NonExistEntitySet\' was not found in the entity sets', $exception->getMessage());
        }


        try {
            $this->_dataServiceConfiguration->setMaxResultsPerCollection(5);
            $this->fail('An expected InvalidOperationException for \'page size and max result per collection mutual exclusion\' was not thrown for month');
        } catch (InvalidOperationException $exception) {
            $this->assertStringEndsWith('mutually exclusive with the specification of \'maximum result per collection\' in configuration', $exception->getMessage());
        }


    }

    public function testConfiguration4()
    {

        $customersResourceSet = $this->_northWindMetadata->resolveResourceSet('Customers');
        $this->assertNotNull($customersResourceSet);
        $this->AssertEquals($this->_dataServiceConfiguration->getEntitySetAccessRule($customersResourceSet), EntitySetRights::NONE);

        try {
            $this->_dataServiceConfiguration->setEntitySetAccessRule('Customers', EntitySetRights::ALL + 1);
            $this->fail('An expected InvalidOperationException for \'page size and max result per collection mutual exclusion\' was not thrown for month');
        } catch (\InvalidArgumentException $exception) {
            $this->AssertEquals('The argument \'$rights\' of \'setEntitySetAccessRule\' should be EntitySetRights enum value', $exception->getMessage());
        }


        $this->_dataServiceConfiguration->setEntitySetAccessRule('Customers', EntitySetRights::READ_ALL);
        $this->AssertEquals($this->_dataServiceConfiguration->getEntitySetAccessRule($customersResourceSet), EntitySetRights::READ_ALL);

        try {
            $this->_dataServiceConfiguration->setEntitySetAccessRule('NonExistEntitySet', EntitySetRights::READ_MULTIPLE);
            $this->fail('An expected InvalidArgumentException for \'non-exist entity set name\' was not thrown for month');
        } catch(\InvalidArgumentException $exception) {
            $this->AssertEquals('The given name \'NonExistEntitySet\' was not found in the entity sets', $exception->getMessage());
        }


    }

    public function testConfiguration5()
    {

        $this->_dataServiceConfiguration->setAcceptCountRequests(true);
        $this->_dataServiceConfiguration->setAcceptProjectionRequests(true);
        $this->_dataServiceConfiguration->setMaxDataServiceVersion(DataServiceProtocolVersion::V1);
        $this->AssertTrue($this->_dataServiceConfiguration->getAcceptCountRequests());
        $this->AssertTrue($this->_dataServiceConfiguration->getAcceptProjectionRequests());

        try {
            $this->_dataServiceConfiguration->validateConfigAganistVersion();
            $this->fail('An expected InvalidOperationException for \'feature not supported for version\' was not thrown for month');
        } catch (InvalidOperationException $exception) {
           $this->AssertEquals('The feature \'projection and count request\' is supported only for OData version \'V2\' or greater', $exception->getMessage());
        }


        $this->_dataServiceConfiguration->setMaxDataServiceVersion(DataServiceProtocolVersion::V2);
        $this->_dataServiceConfiguration->validateConfigAganistVersion();

    }

    protected function tearDown()
    {
        //TODO: verify this is needed!
        unset($this->_dataServiceConfiguration);
        unset($this->_northWindMetadata);
    }
}