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


use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
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

    private readonly EntityManagerInterface $entityManager;

    public function __construct(
        ?EntityManagerInterface $entityManager,
        private readonly ?string $defaultEntityClass = null,
        private readonly bool $prefetchAll = false,
        private readonly ?LoggerInterface $logger = null,
    )
    {
        if ($entityManager === null) {
            throw new \InvalidArgumentException('No entity manager provided! This most likely means that the Doctrine ORM bundle is not installed or properly configured. Install it to use the ORM storage adapter.');
        }

        $this->entityManager = $entityManager;

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
    private function getEntityObject(string $key, string $entityClass): AbstractSettingsORMEntry
    {
        if (!is_subclass_of($entityClass, AbstractSettingsORMEntry::class)) {
            throw new \InvalidArgumentException('The entity class must be a subclass of ' . AbstractSettingsORMEntry::class);
        }

        //Check if we already have the entity in the cache
        if (isset($this->cache[$entityClass][$key])) {
            return $this->cache[$entityClass][$key];
        }

        //Retrieve the entity from the database or create a new one if it does not exist
        $entity = $this->entityManager->getRepository($entityClass)->findOneBy(['key' => $key]);
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
    private function preloadAllEntityObjects(string $entityClass): void
    {
        //If the cache is already filled, we do not need to preload the entities
        if (!empty($this->cache)) {
            return;
        }

        if (!is_subclass_of($entityClass, AbstractSettingsORMEntry::class)) {
            throw new \InvalidArgumentException('The entity class must be a subclass of ' . AbstractSettingsORMEntry::class);
        }

        $entities = $this->entityManager->getRepository($entityClass)->findAll();
        foreach ($entities as $entity) {
            $this->cache[$entityClass][$entity->getKey()] = $entity;
        }
    }

    public function save(string $key, array $data, array $options = []): void
    {
        $entityClass = $options['entity_class'] ?? $this->defaultEntityClass ?? throw new \LogicException('You must either provide an entity class in the options or set a default entity class!');

        //Retrieve the entity object
        $entity = $this->getEntityObject($key, $entityClass);

        //Set the data
        $entity->setData($data);

        //Persist the entity (if not already done)
        $this->entityManager->persist($entity);

        //And save the changes
        $this->entityManager->flush();
    }

    public function load(string $key, array $options = []): ?array
    {
        $entityClass = $options['entity_class'] ?? $this->defaultEntityClass ?? throw new \LogicException('You must either provide an entity class in the options or set a default entity class!');

        //Retrieve the data from database
        try {
            //Preload all entity objects if the fetchAll option is set
            if ($this->prefetchAll) {
                $this->preloadAllEntityObjects($entityClass);
            }

            //Retrieve the entity object
            $entity = $this->getEntityObject($key, $options['entity_class'] ?? $this->defaultEntityClass);

            //Return the data
            return $entity->getData();
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
        }
    }
}