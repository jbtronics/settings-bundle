<?php

namespace Jbtronics\SettingsBundle\Exception;

use Throwable;

class SettingsNotValidException extends \RuntimeException
{
    /**
     * @var array An array of errors in the format of: ['class' => ['property' => ['error1', 'error2', ...], ...], ...]
     */
    private readonly array $errors_per_class;

    public function __construct(array $errors_per_class, Throwable $previous = null)
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

    private static function createForSingleClass(object|string $settings, array $errors_per_property): SettingsNotValidException
    {
        $class = is_object($settings) ? get_class($settings) : $settings;
        return new self([$class => $errors_per_property]);
    }

}