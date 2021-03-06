<?php

namespace POData\Providers\Metadata;

use AlgoWeb\ODataMetadata\MetadataManager;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\TypeCode;

/**
 * Class SimpleMetadataProvider.
 */
class SimpleMetadataProvider implements IMetadataProvider
{
    public $OdataEntityMap = [];
    protected $resourceSets = [];
    protected $resourceTypes = [];
    protected $associationSets = [];
    protected $containerName;
    protected $namespaceName;
    private $metadataManager;

    /**
     * @param string $containerName container name for the datasource
     * @param string $namespaceName namespace for the datasource
     */
    public function __construct($containerName, $namespaceName)
    {
        $this->containerName = $containerName;
        $this->namespaceName = $namespaceName;
        $this->metadataManager = new MetadataManager($namespaceName, $containerName);
    }

    //Begin Implementation of IMetadataProvider

    public function getXML()
    {
        return $this->metadataManager->getEdmxXML();
    }

    /**
     * get the Container name for the data source.
     *
     * @return string container name
     */
    public function getContainerName()
    {
        return $this->containerName;
    }

    /**
     * get Namespace name for the data source.
     *
     * @return string namespace
     */
    public function getContainerNamespace()
    {
        return $this->namespaceName;
    }

    /**
     * get all entity set information.
     *
     * @return ResourceSet[]
     */
    public function getResourceSets($params = null)
    {
        $parameters = [];
        if (is_string($params)) {
            $parameters[] = $params;
        } elseif (isset($params) && !is_array($params)) {
            throw new \ErrorException('Input parameter must be absent, null, string or array');
        } else {
            $parameters = $params;
        }
        if (!is_array($parameters) || 0 == count($parameters)) {
            return array_values($this->resourceSets);
        }
        assert(is_array($parameters));
        $return = [];
        $counter = 0;
        foreach ($this->resourceSets as $resource) {
            $resName = $resource->getName();
            if (in_array($resName, $parameters)) {
                $return[] = $resource;
                $counter++;
            }
        }
        assert($counter == count($return));

        return $return;
    }

    /**
     * get all resource types in the data source.
     *
     * @return ResourceType[]
     */
    public function getTypes()
    {
        return array_values($this->resourceTypes);
    }

    /**
     * get a resource set based on the specified resource set name.
     *
     * @param string $name Name of the resource set
     *
     * @return ResourceSet|null resource set with the given name if found else NULL
     */
    public function resolveResourceSet($name)
    {
        if (array_key_exists($name, $this->resourceSets)) {
            return $this->resourceSets[$name];
        }
        return null;
    }

    /**
     * get a resource type based on the resource type name.
     *
     * @param string $name Name of the resource type
     *
     * @return ResourceType|null resource type with the given resource type name if found else NULL
     */
    public function resolveResourceType($name)
    {
        if (array_key_exists($name, $this->resourceTypes)) {
            return $this->resourceTypes[$name];
        }
    }

    /**
     * get a resource set based on the specified resource association set name.
     *
     * @param string $name Name of the resource assocation set
     *
     * @return ResourceAssociationSet|null resource association set with the given name if found else NULL
     */
    public function resolveAssociationSet($name)
    {
        if (array_key_exists($name, $this->associationSets)) {
            return $this->associationSets[$name];
        }
        return null;
    }

    /**
     * The method must return a collection of all the types derived from
     * $resourceType The collection returned should NOT include the type
     * passed in as a parameter.
     *
     * @param ResourceType $resourceType Resource to get derived resource types from
     *
     * @return ResourceType[]
     */
    public function getDerivedTypes(ResourceType $resourceType)
    {
        return [];
    }

    /**
     * @param ResourceType $resourceType Resource to check for derived resource types
     *
     * @return bool true if $resourceType represents an Entity Type which has derived Entity Types, else false
     */
    public function hasDerivedTypes(ResourceType $resourceType)
    {
        return false;
    }

    //End Implementation of IMetadataProvider

    /**
     * Gets the ResourceAssociationSet instance for the given source
     * association end.
     *
     * @param ResourceSet      $sourceResourceSet      Resource set
     *                                                 of the source
     *                                                 association end
     * @param ResourceType     $sourceResourceType     Resource type of the source
     *                                                 association end
     * @param ResourceProperty $targetResourceProperty Resource property of
     *                                                 the source
     *                                                 association end
     *
     * @return ResourceAssociationSet|null
     */
    public function getResourceAssociationSet(
        ResourceSet $sourceResourceSet,
        ResourceType $sourceResourceType,
        ResourceProperty $targetResourceProperty
    ) {
        //e.g.
        //ResourceSet => Representing 'Customers' entity set
        //ResourceType => Representing'Customer' entity type
        //ResourceProperty => Representing 'Orders' property
        //We have created ResourceAssoicationSet while adding
        //ResourceSetReference or ResourceReference
        //and kept in $this->associationSets
        //$metadata->addResourceSetReferenceProperty(
        //             $customersEntityType,
        //             'Orders',
        //             $ordersResourceSet
        //             );

        $targetResourceSet = $targetResourceProperty->getResourceType()->getCustomState();
        if (is_null($targetResourceSet)) {
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from ' . $targetResourceProperty->getResourceType()->getName()
            );
        }

        //Customer_Orders_Orders, Order_Customer_Customers
        $key = ResourceAssociationSet::keyName(
            $sourceResourceType,
            $targetResourceProperty->getName(),
            $targetResourceSet
        );

        $associationSet = array_key_exists($key, $this->associationSets) ? $this->associationSets[$key] : null;
        assert(
            null == $associationSet || $associationSet instanceof ResourceAssociationSet,
            "Retrieved resource assocation must be either null or an instance of ResourceAssociationSet"
        );
        return $associationSet;
    }

    /**
     * Add an entity type.
     *
     * @param \ReflectionClass $refClass  reflection class of the entity
     * @param string           $name      name of the entity
     * @param string           $namespace namespace of the data source
     *
     * @throws InvalidOperationException when the name is already in use
     *
     * @return ResourceType
     */
    public function addEntityType(\ReflectionClass $refClass, $name, $namespace = null)
    {
        return $this->createResourceType($refClass, $name, $namespace, ResourceTypeKind::ENTITY, null);
    }

    /**
     * @param \ReflectionClass $refClass
     * @param string $name
     * @param string|null $namespace
     * @param $typeKind
     * @param null|ResourceType $baseResourceType
     *
     * @throws InvalidOperationException
     *
     * @return ResourceType
     */
    private function createResourceType(
        \ReflectionClass $refClass,
        $name,
        $namespace,
        $typeKind,
        $baseResourceType
    ) {
        if (array_key_exists($name, $this->resourceTypes)) {
            throw new InvalidOperationException('Type with same name already added');
        }

        $entityType = new ResourceType($refClass, $typeKind, $name, $namespace, $baseResourceType);
        $this->resourceTypes[$name] = $entityType;
        ksort($this->resourceTypes);

        if ($typeKind == ResourceTypeKind::ENTITY) {
            $this->OdataEntityMap[$entityType->getFullName()] = $this->metadataManager->addEntityType($name);
        }


        return $entityType;
    }

    /**
     * Add a complex type.
     *
     * @param \ReflectionClass $refClass         reflection class of the complex entity type
     * @param string           $name             name of the entity
     * @param string           $namespace        namespace of the data source
     * @param ResourceType     $baseResourceType base resource type
     *
     * @throws InvalidOperationException when the name is already in use
     *
     * @return ResourceType
     */
    public function addComplexType(\ReflectionClass $refClass, $name, $namespace = null, $baseResourceType = null)
    {
        return $this->createResourceType($refClass, $name, $namespace, ResourceTypeKind::COMPLEX, $baseResourceType);
    }

    /**
     * @param string       $name         name of the resource set
     * @param ResourceType $resourceType resource type
     *
     * @throws InvalidOperationException
     *
     * @return ResourceSet
     */
    public function addResourceSet($name, ResourceType $resourceType)
    {
        if (array_key_exists($name, $this->resourceSets)) {
            throw new InvalidOperationException('Resource Set already added');
        }

        $this->resourceSets[$name] = new ResourceSet($name, $resourceType);
        //No support for multiple ResourceSet with same EntityType
        //So keeping reference to the 'ResourceSet' with the entity type
        $resourceType->setCustomState($this->resourceSets[$name]);
        ksort($this->resourceSets);

        return $this->resourceSets[$name];
    }

    /**
     * To add a Key-primitive property to a resource (Complex/Entity).
     *
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param string       $name         name of the key property
     * @param TypeCode     $typeCode     type of the key property
     */
    public function addKeyProperty($resourceType, $name, $typeCode)
    {
        $this->_addPrimitivePropertyInternal($resourceType, $name, $typeCode, true);
    }

    /**
     * To add a Key/NonKey-primitive property to a resource (complex/entity).
     *
     * @param ResourceType $resourceType   Resource type
     * @param string       $name           name of the property
     * @param TypeCode     $typeCode       type of property
     * @param bool         $isKey          property is key or not
     * @param bool         $isBag          property is bag or not
     * @param bool         $isETagProperty property is etag or not
     */
    private function _addPrimitivePropertyInternal(
        $resourceType,
        $name,
        $typeCode,
        $isKey = false,
        $isBag = false,
        $isETagProperty = false
    ) {
        $this->checkInstanceProperty($name, $resourceType);

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($name) == strtolower($resourceType->getName())) {
            throw new InvalidOperationException(
                'Property name must be different from resource name.'
            );
        }

        $primitiveResourceType = ResourceType::getPrimitiveResourceType($typeCode);

        if ($isETagProperty && $isBag) {
            throw new InvalidOperationException(
                'Only primitve property can be etag property, bag property cannot be etag property.'
            );
        }

        $kind = $isKey ? ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY : ResourcePropertyKind::PRIMITIVE;
        if ($isBag) {
            $kind = $kind | ResourcePropertyKind::BAG;
        }

        if ($isETagProperty) {
            $kind = $kind | ResourcePropertyKind::ETAG;
        }

        $resourceProperty = new ResourceProperty($name, null, $kind, $primitiveResourceType);
        $resourceType->addProperty($resourceProperty);
        if (array_key_exists($resourceType->getFullName(), $this->OdataEntityMap)) {
            $this->metadataManager->addPropertyToEntityType($this->OdataEntityMap[$resourceType->getFullName()], $name, $primitiveResourceType->getFullName(), null, false, $isKey);
        }
    }

    /**
     * @param string $name
     * @param ResourceType $resourceType
     *
     * @throws InvalidOperationException
     */
    private function checkInstanceProperty($name, ResourceType $resourceType)
    {
        $instance = $resourceType->getInstanceType();
        $hasMagicGetter = $instance instanceof IType || $instance->hasMethod('__get');

        if (!$hasMagicGetter) {
            try {
                if ($instance instanceof \ReflectionClass) {
                    $instance->getProperty($name);
                }
            } catch (\ReflectionException $exception) {
                throw new InvalidOperationException(
                    'Can\'t add a property which does not exist on the instance type.'
                );
            }
        }
    }

    /**
     * To add a NonKey-primitive property (Complex/Entity).
     *
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param string $name name of the key property
     * @param TypeCode $typeCode type of the key property
     * @param bool $isBag property is bag or not
     */
    public function addPrimitiveProperty($resourceType, $name, $typeCode, $isBag = false)
    {
        $this->_addPrimitivePropertyInternal($resourceType, $name, $typeCode, false, $isBag);
    }

    /**
     * To add a non-key etag property.
     *
     * @param ResourceType $resourceType resource type to which key property
     *                                   is to be added
     * @param string $name name of the property
     * @param TypeCode $typeCode type of the etag property
     */
    public function addETagProperty($resourceType, $name, $typeCode)
    {
        $this->_addPrimitivePropertyInternal($resourceType, $name, $typeCode, false, false, true);
    }

    /**
     * To add a resource reference property.
     *
     * @param ResourceType $resourceType The resource type to add the resource
     *                                        reference property to
     * @param string $name The name of the property to add
     * @param ResourceSet $targetResourceSet The resource set the resource reference
     *                                        property points to
     */
    public function addResourceReferenceProperty($resourceType, $name, $targetResourceSet)
    {
        $this->_addReferencePropertyInternal(
            $resourceType,
            $name,
            $targetResourceSet,
            ResourcePropertyKind::RESOURCE_REFERENCE
        );
    }

    /**
     * To add a 1:N resource reference property.
     *
     * @param ResourceType $sourceResourceType  The resource type to add the resource
     *                                          reference property from
     * @param ResourceType $targetResourceType  The resource type to add the resource
     *                                          reference property to
     * @param string $sourceProperty            The name of the property to add, on source type
     * @param string $targetProperty            The name of the property to add, on target type
     */
    public function addResourceReferencePropertyBidirectional(
        ResourceType $sourceResourceType,
        ResourceType $targetResourceType,
        $sourceProperty,
        $targetProperty
    ) {
        $this->_addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            ResourcePropertyKind::RESOURCE_REFERENCE,
            ResourcePropertyKind::RESOURCESET_REFERENCE
        );
    }

    /**
     * To add a navigation property (resource set or resource reference)
     * to a resource type.
     *
     * @param ResourceType         $sourceResourceType   The resource type to add
     *                                                   the resource reference
     *                                                   or resource
     *                                                   reference set property to
     * @param string               $name                 The name of the
     *                                                   property to add
     * @param ResourceSet          $targetResourceSet    The resource set the
     *                                                   resource reference
     *                                                   or reference
     *                                                   set property
     *                                                   points to
     * @param ResourcePropertyKind $resourcePropertyKind The property kind
     */
    private function _addReferencePropertyInternal(
        ResourceType $sourceResourceType,
        $name,
        ResourceSet $targetResourceSet,
        $resourcePropertyKind
    ) {
        $this->checkInstanceProperty($name, $sourceResourceType);

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($name) == strtolower($sourceResourceType->getName())) {
            throw new InvalidOperationException(
                'Property name must be different from resource name.'
            );
        }

        $targetResourceType = $targetResourceSet->getResourceType();
        $sourceResourceProperty = new ResourceProperty($name, null, $resourcePropertyKind, $targetResourceType);
        $sourceResourceType->addProperty($sourceResourceProperty);

        //Create instance of AssociationSet for this relationship
        $sourceResourceSet = $sourceResourceType->getCustomState();
        if (!$sourceResourceSet instanceof ResourceSet) {
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from '
                . $sourceResourceType->getName()
            );
        }

        //Customer_Orders_Orders, Order_Customer_Customers
        //(source type::name _ source property::name _ target set::name)
        $setKey = ResourceAssociationSet::keyName($sourceResourceType, $name, $targetResourceSet);
        //$setKey = $sourceResourceType->getName() . '_' . $name . '_' . $targetResourceType->getName();
        $set = new ResourceAssociationSet(
            $setKey,
            new ResourceAssociationSetEnd($sourceResourceSet, $sourceResourceType, $sourceResourceProperty),
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceType, null)
        );
        $mult = $resourcePropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE ? "*" : "0..1";
        $this->metadataManager->addNavigationPropertyToEntityType(
            $this->OdataEntityMap[$sourceResourceType->getFullName()],
            $mult,
            $name,
            $this->OdataEntityMap[$targetResourceType->getFullName()],
            $mult
        );
        $this->associationSets[$setKey] = $set;
    }

    /**
     * To add a navigation property (resource set or resource reference)
     * to a resource type.
     *
     * @param ResourceType         $sourceResourceType   The source resource type to add
     *                                                   the resource reference
     *                                                   or resource reference set property to
     * @param ResourceType         $targetResourceType   The target resource type to add
     *                                                   the resource reference
     *                                                   or resource reference set property to
     * @param string               $sourceProperty       The name of the
     *                                                   property to add to source type
     * @param string               $targetProperty       The name of the
     *                                                   property to add to target type
     * @param ResourcePropertyKind $sourcePropertyKind   The property kind on the source type
     * @param ResourcePropertyKind $targetPropertyKind   The property kind on the target type
     */
    private function _addReferencePropertyInternalBidirectional(
        ResourceType $sourceResourceType,
        ResourceType $targetResourceType,
        $sourceProperty,
        $targetProperty,
        $sourcePropertyKind,
        $targetPropertyKind
    ) {
        if (!is_string($sourceProperty) || !is_string($targetProperty)) {
            throw new InvalidOperationException("Source and target properties must both be strings");
        }

        $this->checkInstanceProperty($sourceProperty, $sourceResourceType);
        $this->checkInstanceProperty($targetProperty, $targetResourceType);

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($sourceProperty) == strtolower($sourceResourceType->getName())) {
            throw new InvalidOperationException(
                'Source property name must be different from source resource name.'
            );
        }
        if (strtolower($targetProperty) == strtolower($targetResourceType->getName())) {
            throw new InvalidOperationException(
                'Target property name must be different from target resource name.'
            );
        }

        //Create instance of AssociationSet for this relationship
        $sourceResourceSet = $sourceResourceType->getCustomState();
        if (!$sourceResourceSet instanceof ResourceSet) {
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from '
                . $sourceResourceType->getName()
            );
        }
        $targetResourceSet = $targetResourceType->getCustomState();
        if (!$targetResourceSet instanceof ResourceSet) {
            throw new InvalidOperationException(
                'Failed to retrieve the custom state from '
                . $targetResourceType->getName()
            );
        }

        $sourceResourceProperty = new ResourceProperty($sourceProperty, null, $sourcePropertyKind, $targetResourceType);
        $sourceResourceType->addProperty($sourceResourceProperty, false);
        $targetResourceProperty = new ResourceProperty($targetProperty, null, $targetPropertyKind, $sourceResourceType);
        $targetResourceType->addProperty($targetResourceProperty, false);

        //Customer_Orders_Orders, Order_Customer_Customers
        $fwdSetKey = ResourceAssociationSet::keyName($sourceResourceType, $sourceProperty, $targetResourceSet);
        $revSetKey = ResourceAssociationSet::keyName($targetResourceType, $targetProperty, $sourceResourceSet);
        $fwdSet = new ResourceAssociationSet(
            $fwdSetKey,
            new ResourceAssociationSetEnd($sourceResourceSet, $sourceResourceType, $sourceResourceProperty),
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceType, $targetResourceProperty)
        );
        $revSet = new ResourceAssociationSet(
            $revSetKey,
            new ResourceAssociationSetEnd($targetResourceSet, $targetResourceType, $targetResourceProperty),
            new ResourceAssociationSetEnd($sourceResourceSet, $sourceResourceType, $sourceResourceProperty)
        );
        $sourceName = $sourceResourceType->getFullName();
        $targetName = $targetResourceType->getFullName();
        $sourceMult = $sourcePropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE ? '*' : '0..1';
        $targetMult = $targetPropertyKind == ResourcePropertyKind::RESOURCESET_REFERENCE ? '*' : '0..1';
        $this->metadataManager->addNavigationPropertyToEntityType(
            $this->OdataEntityMap[$sourceName],
            $sourceMult,
            $sourceProperty,
            $this->OdataEntityMap[$targetName],
            $targetMult,
            $targetProperty
        );
        $this->associationSets[$fwdSetKey] = $fwdSet;
        $this->associationSets[$revSetKey] = $revSet;
    }

    /**
     * To add a resource set reference property.
     *
     * @param ResourceType $resourceType The resource type to add the
     *                                        resource reference set property to
     * @param string $name The name of the property to add
     * @param ResourceSet $targetResourceSet The resource set the resource
     *                                        reference set property points to
     */
    public function addResourceSetReferenceProperty($resourceType, $name, $targetResourceSet)
    {
        $this->_addReferencePropertyInternal(
            $resourceType,
            $name,
            $targetResourceSet,
            ResourcePropertyKind::RESOURCESET_REFERENCE
        );
    }

    /**
     * To add a M:N resource reference property.
     *
     * @param ResourceType $sourceResourceType  The resource type to add the resource
     *                                          reference property from
     * @param ResourceType $targetResourceType  The resource type to add the resource
     *                                          reference property to
     * @param string $sourceProperty            The name of the property to add, on source type
     * @param string $targetProperty            The name of the property to add, on target type
     */
    public function addResourceSetReferencePropertyBidirectional(
        ResourceType $sourceResourceType,
        ResourceType $targetResourceType,
        $sourceProperty,
        $targetProperty
    ) {
        $this->_addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            ResourcePropertyKind::RESOURCESET_REFERENCE,
            ResourcePropertyKind::RESOURCESET_REFERENCE
        );
    }

    /**
     * To add a 1-1 resource reference.
     *
     * @param ResourceType $sourceResourceType  The resource type to add the resource
     *                                          reference property from
     * @param ResourceType $targetResourceType  The resource type to add the resource
     *                                          reference property to
     * @param string $sourceProperty            The name of the property to add, on source type
     * @param string $targetProperty            The name of the property to add, on target type
     */
    public function addResourceReferenceSinglePropertyBidirectional(
        ResourceType $sourceResourceType,
        ResourceType $targetResourceType,
        $sourceProperty,
        $targetProperty
    ) {
        $this->_addReferencePropertyInternalBidirectional(
            $sourceResourceType,
            $targetResourceType,
            $sourceProperty,
            $targetProperty,
            ResourcePropertyKind::RESOURCE_REFERENCE,
            ResourcePropertyKind::RESOURCE_REFERENCE
        );
    }

    /**
     * To add a complex property to entity or complex type.
     *
     * @param ResourceType $resourceType The resource type to which the
     *                                          complex property needs to add
     * @param string $name name of the complex property
     * @param ResourceType $complexResourceType complex resource type
     * @param bool $isBag complex type is bag or not
     *
     * @return ResourceProperty
     */
    public function addComplexProperty($resourceType, $name, $complexResourceType, $isBag = false)
    {
        if ($resourceType->getResourceTypeKind() != ResourceTypeKind::ENTITY
            && $resourceType->getResourceTypeKind() != ResourceTypeKind::COMPLEX
        ) {
            throw new InvalidOperationException('Complex property can be added to an entity or another complex type');
        }

        // check that property and resource name don't up and collide - would violate OData spec
        if (strtolower($name) == strtolower($resourceType->getName())) {
            throw new InvalidOperationException(
                'Property name must be different from resource name.'
            );
        }

        $this->checkInstanceProperty($name, $resourceType);

        $kind = ResourcePropertyKind::COMPLEX_TYPE;
        if ($isBag) {
            $kind = $kind | ResourcePropertyKind::BAG;
        }

        $resourceProperty = new ResourceProperty($name, null, $kind, $complexResourceType);
        $resourceType->addProperty($resourceProperty);

        return $resourceProperty;
    }
}
