<?php
/*
 * This file is part of jbtronics/settings-bundle (https://github.com/jbtronics/settings-bundle).
 *
 * Copyright (c) 2024 Jan Böhmer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);


namespace Jbtronics\SettingsBundle\Storage;


use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Jbtronics\SettingsBundle\Entity\AbstractSettingsORMEntry;
use Psr\Log\LoggerInterface;

/**
 * This class provides a storage adapter for the Doctrine ORM, it allows to store settings in the database using Doctrine ORM entities.
 * You will need to implement your own entity class that extends AbstractSettingsORMEntry and configure the storage adapter to use it.
 */
final class ORMStorageAdapter implements StorageAdapterInterface
{

    /**
     * @var AbstractSettingsORMEntry[][]
     * @phpstan-var array<string, array<AbstractSettingsORMEntry>>
     */
    private array $cache = [];

    private readonly ManagerRegistry $managerRegistry;

    public function __construct(
        ?ManagerRegistry $managerRegistry,
        private readonly ?string $defaultEntityClass = null,
        private readonly bool $prefetchAll = false,
        private readonly ?LoggerInterface $logger = null,
    )
    {
        if ($managerRegistry === null) {
            throw new \InvalidArgumentException('No manager registry provided! This most likely means that the Doctrine ORM bundle is not installed or properly configured. Install it to use the ORM storage adapter.');
        }

        $this->managerRegistry = $managerRegistry;

        if ($this->defaultEntityClass !== null && !is_subclass_of($this->defaultEntityClass, AbstractSettingsORMEntry::class)) {
            throw new \InvalidArgumentException('The default entity class must be a subclass of ' . AbstractSettingsORMEntry::class);
        }
    }

    /**
     * Returns the entity object for the given key. If the entity does not exist, it will be created.
     * @param  string  $key
     * @param  string  $entityClass The class of the entity to use

     * @return AbstractSettingsORMEntry
     */
    private function getEntityObject(ObjectManager $entityManager, string $key, string $entityClass): AbstractSettingsORMEntry
    {
        if (!is_subclass_of($entityClass, AbstractSettingsORMEntry::class)) {
            throw new \InvalidArgumentException('The entity class must be a subclass of ' . AbstractSettingsORMEntry::class);
        }

        //Check if we already have the entity in the cache
        if (isset($this->cache[$entityClass][$key])) {
            return $this->cache[$entityClass][$key];
        }

        //Retrieve the entity from the database or create a new one if it does not exist
        $entity = $entityManager->getRepository($entityClass)->findOneBy(['key' => $key]);
        if ($entity === null) {
            $entity = new $entityClass($key);
        }

        //Store the entity in the cache
        $this->cache[$entityClass][$key] = $entity;

        return $entity;
    }

    /**
     * This function preloads all entity objects of the given class into the cache, so that consecutive calls to getEntityObject() do not require a database query.
     * @param  string  $entityClass
     * @return void
     */
    private function preloadAllEntityObjects(ObjectManager $entityManager, string $entityClass): void
    {
        //If the cache is already filled, we do not need to preload the entities
        if (!empty($this->cache)) {
            return;
        }

        if (!is_subclass_of($entityClass, AbstractSettingsORMEntry::class)) {
            throw new \InvalidArgumentException('The entity class must be a subclass of ' . AbstractSettingsORMEntry::class);
        }

        $entities = $entityManager->getRepository($entityClass)->findAll();
        foreach ($entities as $entity) {
            $this->cache[$entityClass][$entity->getKey()] = $entity;
        }
    }

    public function save(string $key, array $data, array $options = []): void
    {
        $entityClass = $options['entity_class'] ?? $this->defaultEntityClass ?? throw new \LogicException('You must either provide an entity class in the options or set a default entity class!');

        //Get the manager for the entity class
        $entityManager = $this->getEntityManager($entityClass, $options);

        //Retrieve the entity object
        $entity = $this->getEntityObject($entityManager, $key, $entityClass);

        //reFetch if detached
        if (!$entityManager->contains($entity)) {
            $id = $entityManager->getClassMetadata($entityClass)->getIdentifierValues($entity);
            if ([] !== $id) {
                $entity = $entityManager->find($entityClass, $id) ?? $entity;
                $this->cache[$entityClass][$key] = $entity;
            }
        }

        //Set the data
        $entity->setData($data);

        //Persist the entity (if not already done)
        $entityManager->persist($entity);

        //And save the changes
        $entityManager->flush();
    }

    public function load(string $key, array $options = []): ?array
    {
        $entityClass = $options['entity_class'] ?? $this->defaultEntityClass ?? throw new \LogicException('You must either provide an entity class in the options or set a default entity class!');

        //Get the manager for the entity class
        $entityManager = $this->getEntityManager($entityClass, $options);

        //Retrieve the data from database
        try {
            //Preload all entity objects if the fetchAll option is set
            if ($this->prefetchAll) {
                $this->preloadAllEntityObjects($entityManager, $entityClass);
            }

            //Retrieve the entity object & return the data
            return $this->getEntityObject($entityManager, $key, $entityClass)->getData();
        } catch (TableNotFoundException $exception) {
            //If the table does not exist, we fail gracefully and return null to indicate that no data was persisted yet

            //If a logger is available, log the problem, so that the user knows he still need to create the table
            if ($this->logger !== null) {
                $this->logger->warning(
                    'The table for the settings entity does not exist yet. Use doctrine schema:dump or doctrine migrations tools to create the table.'
                    . ' Otherwise an exception will be thrown when trying to save the settings. For now we just assume that no data was persisted yet and the default values should be used.'
                    . ' The exception was: ' . $exception->getMessage(),
                    ['exception' => $exception]
                );
            }

            return null;
        } catch (ConnectionException $exception) {
            //If the connection to the database failed, we fail gracefully and return null to indicate that no data was persisted yet

            //If a logger is available, log the problem, so that the user knows he still need to create the table
            if ($this->logger !== null) {
                $this->logger->error(
                    'The connection to the database failed. The settings can not be loaded. Default values for the settings will be used. The exception was: ' . $exception->getMessage(),
                    ['exception' => $exception]
                );
            }

            return null;
        }
    }

    private function getEntityManager(string $entityClass, array $options): ObjectManager
    {
        //Check if a entity manager is specified in the options
        if (isset($options['entity_manager'])) {
            return $this->managerRegistry->getManager($options['entity_manager']);
        }

        //Otherwise use the object manager for the given class
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        if ($manager === null) {
            throw new \InvalidArgumentException('No entity manager found for class ' . $entityClass . '. Make sure the entity class is correctly mapped in your Doctrine configuration, or try to specify the entityManager in the options manually.');
        }
        
        return $manager;
    }
}