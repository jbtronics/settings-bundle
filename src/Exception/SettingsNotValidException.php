<?php


/*
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

namespace Jbtronics\SettingsBundle\Exception;

use Throwable;

class SettingsNotValidException extends \RuntimeException
{
    /**
     * @var array An array of errors in the format of: ['class' => ['property' => ['error1', 'error2', ...], ...], ...]
     */
    private readonly array $errors_per_class;

    public function __construct(array $errors_per_class, ?Throwable $previous = null)
    {
        $this->errors_per_class = $errors_per_class;

        $message = self::generateMessage($errors_per_class);
        parent::__construct($message, 0, $previous);
    }

    /**
     * Generate the exception message from the given errors
     * @param  array  $errors_per_class
     * @return string
     */
    private static function generateMessage(array $errors_per_class): string
    {
        $message = "The following settings classes are not valid: ";
        foreach ($errors_per_class as $class => $errors_per_property) {
            $message .= "\n$class:";
            foreach ($errors_per_property as $property => $errors) {
                $message .= "\n\t$property: " . implode(', ', $errors);
            }
        }
        return $message;
    }

    /**
     * Returns a list of all invalid settings classes. (their class names
     * @return string[]
     * @phpstan-return array<class-string>
     */
    public function getInvalidClasses(): array
    {
        return array_keys($this->errors_per_class);
    }

    public function getInvalidProperties(string $class): array
    {
        return array_keys($this->errors_per_class[$class] ?? throw new \InvalidArgumentException("Class $class is not invalid"));
    }

    public static function createForSingleClass(object|string $settings, array $errors_per_property): SettingsNotValidException
    {
        $class = is_object($settings) ? get_class($settings) : $settings;
        return new self([$class => $errors_per_property]);
    }

}