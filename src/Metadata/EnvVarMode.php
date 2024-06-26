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


namespace Jbtronics\SettingsBundle\Metadata;

/**
 * This enum represents the different modes in which the environment variables can be used to fill parameters.
 */
enum EnvVarMode
{
    /**
     * The value from the environment variable is used as initialisation value, if the parameter was not defined before.
     * If the parameter is defined in the storage, then the value from the storage is used.
     */
    case INITIAL;

    /**
     * The value from the environment variable will always overwrite the value from the storage, no matter if the
     * parameter data was saved in the storage before. The overwritten value however will never be written back to the
     * storage.
     */
    case OVERWRITE;

    /**
     * The value from the environment variable will always overwrite the value from the storage, no matter if the
     * parameter data was saved in the storage before. The difference to OVERWRITE is, that the overwritten value will
     * be written back to the storage on the next persist operation.
     */
    case OVERWRITE_PERSIST;
}
