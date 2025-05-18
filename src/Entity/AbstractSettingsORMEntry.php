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


namespace Jbtronics\SettingsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * This class is the base entity class for the entities to use with the ORMStorageAdapter, it provides a basic
 * key-value based storage for settings.
 */
#[MappedSuperclass]
abstract class AbstractSettingsORMEntry
{
    #[ORM\Column(name: '`key`', type: Types::STRING, length: 255, unique: true, nullable: false)]
    protected string $key;

    #[ORM\Column(name: '`data`', type: Types::JSON, nullable: true)]
    protected ?array $data = null;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * Returns the key of this settings entry
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Sets the key of this settings entry. The key must be unique!
     * @param string $key
     * @return $this
     */
    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    /**
     * Returns the data of this settings entry or null if no data is set
     * @return array|null The normalized data of this settings entry
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * Sets the data of this settings entry or null if no data is set
     * @param array|null $data The normalized data to store
     * @return $this
     */
    public function setData(?array $data): self
    {
        $this->data = $data;
        return $this;
    }


}