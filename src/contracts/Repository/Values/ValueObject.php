<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Repository\Values;

use Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException;
use Symfony\Component\Serializer\Annotation\Ignore as SerializerIgnore;

/**
 * The base class for all value objects and structs.
 *
 * Supports read-only properties by marking them as protected.
 * In this case they will only be writable using constructor, and need to be documented
 * using `@property-read <type> <$var>` annotation in class doc in addition to inline property doc.
 * Writable properties must be public and must be documented inline.
 */
abstract class ValueObject
{
    /**
     * Construct object optionally with a set of properties.
     *
     * Readonly properties values must be set using `$properties` as they aren't writable anymore
     * after object has been created.
     *
     * @param array<string, mixed> $properties
     */
    public function __construct(array $properties = [])
    {
        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }
    }

    /**
     * Returns list of available properties' names.
     *
     * {@internal Used by {@see ValueObject::attributes()}.}
     * Override to add dynamic properties.
     *
     * @uses ValueObject::__isset()
     *
     * @todo Make object traversable and reuse this function there (hence why this is not exposed)
     *
     * @param array<int, string> $dynamicProperties Additional dynamic properties exposed on the object
     *
     * @return array<int, string>
     */
    #[SerializerIgnore]
    protected function getProperties($dynamicProperties = [])
    {
        $properties = $dynamicProperties;
        foreach (get_object_vars($this) as $property => $propertyValue) {
            if ($this->__isset($property)) {
                $properties[] = $property;
            }
        }

        return $properties;
    }

    /**
     * Magic set function handling writes to non-public properties.
     *
     * @internal
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException When property does not exist
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException When property is readonly (protected)
     *
     * @param string $property Name of the property
     * @param string $value
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            throw new PropertyReadOnlyException($property, static::class);
        }
        throw new PropertyNotFoundException($property, static::class);
    }

    /**
     * Magic get function handling read to non public properties.
     *
     * Returns value for all readonly (protected) properties.
     *
     * @internal
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException exception on all reads to undefined properties so typos are not silently accepted.
     *
     * @param string $property Name of the property
     *
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        throw new PropertyNotFoundException($property, static::class);
    }

    /**
     * Magic isset function handling isset() to non public properties.
     *
     * Returns true for all (public/)protected/private properties.
     *
     * @internal
     *
     * @param string $property Name of the property
     *
     * @return bool
     */
    public function __isset($property)
    {
        return property_exists($this, $property);
    }

    /**
     * Magic unset function handling unset() to non public properties.
     *
     * @internal
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException exception on all writes to undefined properties so typos are not silently accepted and
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyReadOnlyException exception on readonly (protected) properties.
     *
     * @uses ValueObject::__set()
     *
     * @param string $property Name of the property
     *
     * @return bool
     */
    public function __unset($property)
    {
        $this->__set($property, null);
    }

    /**
     * Returns a new instance of this class with the data specified by $array.
     *
     * $array contains all the data members of this class in the form:
     * array('member_name'=>value).
     *
     * __set_state makes this class exportable with var_export.
     * var_export() generates code, that calls this method when it
     * is parsed with PHP.
     *
     * @internal
     *
     * @param mixed[] $array
     *
     * @return ValueObject
     */
    public static function __set_state(array $array)
    {
        return new static($array);
    }

    /**
     * Internal function for Legacy template engine compatibility to get property value.
     *
     * @internal
     *
     * @deprecated Since 5.0, available purely for legacy eZTemplate compatibility
     *
     * @uses ValueObject::__get()
     *
     * @param string $property
     *
     * @return mixed
     */
    final public function attribute($property)
    {
        return $this->__get($property);
    }

    /**
     * Internal function for Legacy template engine compatibility to get properties.
     *
     * @internal
     *
     * @deprecated Since 5.0, available purely for legacy eZTemplate compatibility
     *
     * @uses ValueObject::getProperties()
     * @uses ValueObject::__isset()
     *
     * @return array
     */
    final public function attributes()
    {
        return $this->getProperties();
    }

    /**
     * Internal function for Legacy template engine compatibility to check existence of property.
     *
     * @internal
     *
     * @deprecated Since 5.0, available purely for legacy eZTemplate compatibility
     *
     * @uses ValueObject::__isset()
     *
     * @param string $property
     *
     * @return bool
     */
    #[SerializerIgnore]
    final public function hasAttribute($property)
    {
        return $this->__isset($property);
    }
}
