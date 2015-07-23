<?php

namespace Quartz\Component\Form;

/**
 * Description of QuartzEntityForm
 *
 * @author paul
 */
class QuartzEntityForm extends Form
{
    /**
     *
     * @var \Quartz\Object\Entity
     */
    protected $object = null;
    
    public function __construct(\Quartz\Object\Table $table = null)
    {
        parent::__construct();
        
        if( !is_null($table) )
        {
            foreach( $table->getColumns() as $property => $propertiesConfiguration)
            {
                // do not include autoincrement values
                if( in_array($propertiesConfiguration['type'][0], ['sequence', 'bigsequence', 'serial', 'bigserial']) )
                {
                    continue;
                }
                
                $field = $this->addField($property);
                if( $propertiesConfiguration['notnull'] || $propertiesConfiguration['primary'] )
                {
                    $field->setMandatory(true);
                }

                $defaultValue = $propertiesConfiguration['value'];
                if( is_null($defaultValue) && $field->isMandatory() )
                {
                    $field->setDefaultAsNotSetValue();
                }
                else
                {
                    $field->setDefaultValue($defaultValue);
                }
            }
        }
    }
    
    /**
     * 
     * @return \Quartz\Object\Entity
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * 
     * @param \Quartz\Object\Entity $entity
     * @return \Ongoo\Component\Form\QuartzEntityForm
     */
    public function initializeWithEntity(\Quartz\Object\Entity $entity)
    {
        $table = $entity->getTable();

        foreach ($this->fields as $fieldName => $field)
        {
            $getter = $entity->getGetter($fieldName);
            if ($entity->has($fieldName))
            {
                $value = $entity->$getter();
                $field->initializeWith($value);
            } elseif (method_exists($entity, $getter))
            {
                $value = $entity->$getter();
                $field->initializeWith($value);
            }
        }
        return $this;
    }

    protected function fireBeforeBindCallback(\Quartz\Object\Entity &$object)
    {
        
    }

    protected function fireAfterBindCallback(\Quartz\Object\Entity &$object)
    {
        return $object;
    }
    
    /**
     * 
     * @param \Quartz\Object\Entity $object
     * @return \Ongoo\Component\Form\Form
     */
    public function bindTo(\Quartz\Object\Entity &$object)
    {
        $this->fireBeforeBindCallback($object);

        $this->changes = null;
        foreach ($this->fields as $fieldName => $field)
        {
            if (!$field->hasError() && $field->isValueSet())
            {
                try
                {
                    $getter = $object->getGetter($fieldName);
                    $setter = $object->getSetter($fieldName);

                    if ((method_exists($object, $getter) && method_exists($object, $setter)) || $object->has($fieldName))
                    {
                        $object->$setter($field->getValue());
                        $field->setValue($object->$getter());
                    }
                } catch (\Exception $e)
                {
                    $this->getField($fieldName)->addError($e->getMessage());
                }
            }
        }
        $this->object = $this->fireAfterBindCallback($object);
        return $this;
    }
}
