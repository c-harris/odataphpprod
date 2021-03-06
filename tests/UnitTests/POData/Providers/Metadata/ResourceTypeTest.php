<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\Type\IType;
use ReflectionClass;
use UnitTests\POData\ObjectModel\reusableEntityClass2;
use UnitTests\POData\TestCase;

class ResourceTypeTest extends TestCase
{
    public function testGetPrimitiveResourceTypeByte()
    {
        $type = EdmPrimitiveType::BYTE;
        $result = ResourceType::getPrimitiveResourceType($type);
        $this->assertTrue($result instanceof ResourceType);
        $this->assertEquals('Byte', $result->getName());
        $this->assertEquals('Edm', $result->getNamespace());
        $this->assertEquals('Edm.Byte', $result->getFullName());
    }

    public function testGetPrimitiveResourceTypeSByte()
    {
        $type = EdmPrimitiveType::SBYTE;
        $result = ResourceType::getPrimitiveResourceType($type);
        $this->assertTrue($result instanceof ResourceType);
        $this->assertEquals('SByte', $result->getName());
        $this->assertEquals('Edm', $result->getNamespace());
        $this->assertEquals('Edm.SByte', $result->getFullName());
    }

    public function testHasETagPropertiesYes()
    {
        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getETagProperties')->andReturn(['a', 'b'])->once();
        $this->assertTrue($foo->hasETagProperties());
    }

    public function testHasETagPropertiesNo()
    {
        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getETagProperties')->andReturn()->once();
        $this->assertFalse($foo->hasETagProperties());
    }

    public function testGetETagProperties()
    {
        $property = m::mock(ResourceType::class);
        $property->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::ETAG])->andReturn(true);
        $property->shouldReceive('getName')->andReturn('property');

        $foo = m::mock(ResourceType::class)->makePartial();
        $foo->shouldReceive('getAllProperties')->andReturn(['name' => $property]);
        $result = $foo->getETagProperties();
        $this->assertTrue(is_array($result));
        $this->assertTrue($result['name'] instanceof ResourceType);
        $this->assertEquals('property', $result['name']->getName());
    }

    public function testAddNamedStreamWhenNotEntityThrowException()
    {
        $info = m::mock(ResourceStreamInfo::class);
        $foo = m::mock(ResourceType::class)->makePartial();

        $expected = 'Named streams can only be added to entity types.';
        $actual = null;

        try {
            $foo->addNamedStream($info);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testHasBagPropertyCheckTwice()
    {
        $foo = m::mock(ResourceType::class)->makePartial();

        $bar = [];
        $this->assertNull($foo->hasBagProperty($bar));
        $this->assertNull($foo->hasBagProperty($bar));
    }

    public function testTryResolveNamedStream()
    {
        $foo = m::mock(ResourceType::class)->makePartial();
        $this->assertNull($foo->tryResolveNamedStreamDeclaredOnThisTypeByName('foo'));
    }

    public function testSleepWakeupRealObjectITypeRoundTrip()
    {
        $instanceType = m::mock(IType::class);
        $resourceTypeKind = ResourceTypeKind::PRIMITIVE;
        $foo = new ResourceType($instanceType, $resourceTypeKind, 'name');

        $result = $foo->__sleep();

        $expected = ['name', 'namespaceName', 'fullName', 'resourceTypeKind', 'abstractType', 'baseType',
            'propertiesDeclaredOnThisType', 'namedStreamsDeclaredOnThisType', 'allProperties', 'allNamedStreams',
            'eTagProperties', 'keyProperties', 'isMediaLinkEntry', 'hasBagProperty', 'hasNamedStreams', 'type',
            'customState', 'arrayToDetectLoopInComplexBag', ];

        foreach ($expected as $property) {
            $this->assertTrue(in_array($property, $result), $property);
        }

        $foo->__wakeup();
    }

    public function testSleepWakeupRealObjectReflectableRoundTrip()
    {
        $instanceType = new reusableEntityClass2('foo', 'bar');
        $resourceTypeKind = ResourceTypeKind::COMPLEX;
        $foo = new ResourceType(new ReflectionClass($instanceType), $resourceTypeKind, 'name');

        $result = $foo->__sleep();

        $expected = ['name', 'namespaceName', 'fullName', 'resourceTypeKind', 'abstractType', 'baseType',
            'propertiesDeclaredOnThisType', 'namedStreamsDeclaredOnThisType', 'allProperties', 'allNamedStreams',
            'eTagProperties', 'keyProperties', 'isMediaLinkEntry', 'hasBagProperty', 'hasNamedStreams', 'type',
            'customState', 'arrayToDetectLoopInComplexBag', ];

        foreach ($expected as $property) {
            $this->assertTrue(in_array($property, $result), $property);
        }

        $foo->__wakeup();
    }
}
