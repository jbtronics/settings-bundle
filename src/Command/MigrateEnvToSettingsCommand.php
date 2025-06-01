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


namespace Jbtronics\SettingsBundle\Command;

use Jbtronics\SettingsBundle\Manager\SettingsManagerInterface;
use Jbtronics\SettingsBundle\Manager\SettingsRegistry;
use Jbtronics\SettingsBundle\Manager\SettingsRegistryInterface;
use Jbtronics\SettingsBundle\Metadata\MetadataManagerInterface;
use Jbtronics\SettingsBundle\Metadata\SettingsMetadata;
use Jbtronics\SettingsBundle\Migrations\EnvVarToSettingsMigratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: "settings:migrate-env-to-settings", description: "Migrates environment variables to stored settings parameters")]
class MigrateEnvToSettingsCommand extends Command
{
    public function __construct(
        private readonly SettingsRegistryInterface $settingsRegistry,
        private readonly MetadataManagerInterface $metadataManager,
        private readonly EnvVarToSettingsMigratorInterface $envVarToSettingsMigrator,
        )
    {
        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setHelp('This command allows to migrate environment variables to settings parameters. The env variables will be applied to the selected settings objects and all parameters will be stored, no matter of the envVarMode. This is useful if you want to use the settings system instead of environment variables for configuration.')
            ->addArgument("settings", description: "The class of the setting that should be migrated. If this is not set, the user will be asked which setting should be migrated.")
            ->addOption('all', "a", description: "Migrate the environment variables of all settings available in the application")
            ->addOption('no-validation', description: "Do not validate the settings before saving them. This will skip the validation step and save the settings directly, which can lead to invalid settings being saved.")
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $noValidation = $input->getOption('no-validation');

        //Collect the settings that should be migrated
        $settingsToMigrate = [];

        if ($input->getOption('all')) {
            //If the user wants to migrate all settings, we just use the settings registry to get all settings
            $settingsToMigrate = array_map($this->metadataManager->getSettingsMetadata(...), $this->settingsRegistry->getSettingsClasses());
        } else if ($settingArg = $input->getArgument('settings')) {
            //Check if the setting is a class
            if (class_exists($settingArg)) {
                $settingsClass = $settingArg;
            } else { //Otherwise try to resolve a settings name to a class
                $settingsClass = $this->settingsRegistry->getSettingsClassByName($settingArg);
            }

            //Retrieve the settings metadata for the given class
            $settingsToMigrate[] = $this->metadataManager->getSettingsMetadata($settingsClass);
        } else { //Ask the user to choose a setting
            $availableSettings =  $settingsToMigrate = array_map($this->metadataManager->getSettingsMetadata(...), $this->settingsRegistry->getSettingsClasses());
            $settingsToMigrate = $this->settingsSelect($availableSettings, $io);
        }

        //Gather affected env variables
        $affectedEnvVars = [];
        foreach ($settingsToMigrate as $metadata) {
           $affectedEnvVars = array_merge(
                $affectedEnvVars,
                $this->envVarToSettingsMigrator->getAffectedEnvVars($metadata)
            );
        }

        $affectedEnvVars = array_unique($affectedEnvVars);

        if (count($affectedEnvVars) === 0) {
            if (!$io->confirm("It seems that no environment variables would get migrated. Do you still want to continue?", false)) {
                return Command::FAILURE;
            }
        }

        $io->note(sprintf("The following environment variables will be migrated: %s", implode(", ", $affectedEnvVars)));

        if($noValidation) {
            $io->warning("Validation of settings will be skipped! This can lead to invalid settings being saved. Use this option with caution!");
        }

        if (!$io->confirm("Do you want to continue with the migration?", true)) {
            $io->error("Migration aborted by user.");
            return Command::FAILURE;
        }
        $io->info("Starting migration...");
        //Perform the migration

        foreach ($settingsToMigrate as $metadata) {
            /** @var SettingsMetadata $metadata */
            $this->envVarToSettingsMigrator->migrate($metadata, !$noValidation);
        }
        $io->success("Migration completed successfully!");

        $io->info("You can now remove following environment variables from your .env/docker-compose.yml file or similar: " . implode(", ", $affectedEnvVars));

        return Command::SUCCESS;
    }

    protected function settingsSelect(array $settings, SymfonyStyle $io): array
    {
        $settingsNumbered = [];

        $choices = [];
        foreach ($settings as $metadata) {
            /** @var SettingsMetadata $metadata */
            $choices[] = $metadata->getName() . ' (' . $metadata->getClassName() . ')';
            $settingsNumbered[] = $metadata;
        }

        $choices["all"] = "All settings";

        $result = $io->choice("Select the settings to migrate", $choices, null, multiSelect: true);

        //Map the selected choices back to the settings metadata objects
        $selectedSettings = [];
        foreach ($result as $choice) {
            if ($choice === "all") {
                return $settings; //Return all settings if "all" was selected
            }

            $selectedSettings[] = $settingsNumbered[(int) $choice];
        }

        return $selectedSettings;
    }
}