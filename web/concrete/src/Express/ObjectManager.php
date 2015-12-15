<?php
namespace Concrete\Core\Express;

use Concrete\Core\Application\Application;
use Concrete\Core\Cache\Adapter\DoctrineCacheDriver;
use Concrete\Core\Database\EntityManagerFactoryInterface;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\AttributeKey\AttributeKey;
use Concrete\Core\Entity\AttributeValue\AttributeValue;
use Concrete\Core\Entity\Express\Association;
use Concrete\Core\Entity\Express\Form;
use Concrete\Core\Express\Form\Control\SaveHandler\SaveHandlerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Concrete\Core\Entity\Express\Entity;
use Config;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntityManagerFactory
 * @package Concrete\Core\Express
 * The backend entity manager hooks into Doctrine and is called by the front-end
 * entity manager.
 */
class ObjectManager
{

    protected $application;
    protected $entityManager;
    protected $namespace;

    public function __construct(EntityManager $entityManager, Application $application)
    {
        $this->entityManager = $entityManager;
        $this->application = $application;
        $this->namespace = $application['config']->get('express.entity_classes.namespace');
    }

    /**
     * @return mixed
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param mixed $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    public function getClassName(Entity $entity)
    {
        return '\\' . $this->getNamespace() . '\\' . $entity->getName();
    }

    public function create(Entity $entity)
    {
        $class = $this->getClassName($entity);
        $entity = new $class();
        return $entity;
    }

    public function saveFromRequest(Form $form, BaseEntity $entity, Request $request)
    {
        foreach($form->getControls() as $control) {
            $type = $control->getControlType();
            /**
             * @var $type \Concrete\Core\Express\Form\Control\Type\TypeInterface
             */
            $saver = $type->getSaveHandler($control);
            if ($saver instanceof SaveHandlerInterface) {
                $saver->saveFromRequest($this, $control, $entity, $request);
            }
        }
        $this->save($entity);
    }

    public function setAttribute(BaseEntity $entity, AttributeKey $key, AttributeValue $value)
    {
        $method = camelcase($key->getAttributeKeyHandle());
        $method = "set{$method}";
        $entity->$method($value);
    }

    public function save(BaseEntity $entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
