<?php

// app/Console/Commands/ManageSettingsCommand.php

namespace App\Console\Commands;

use App\Models\AllowedSettingValue;
use App\Models\Setting;
use Illuminate\Console\Command;

class ManageSettingsCommand extends Command
{
    protected $signature = 'settings:manage 
        {action? : Action to perform (create-allowed|show-all|check-invalid|update-value|show-allowed)}
        {--key= : Setting key for create/update actions (e.g., "display.theme")}
        {--value= : Setting value for create/update actions}
        {--caption= : Human readable caption for create-allowed action}
        {--old-value= : Old value to replace in update action}
        {--new-value= : New value to set in update action}';

    protected $description = 'Manage application settings and allowed values. 
    Use this command to create allowed settings, view all settings, check for invalid settings, 
    update values across all settings, and show allowed setting values.';

    public function handle()
    {
        if (! $this->argument('action')) {
            $this->info('Available actions:');
            $this->newLine();
            $this->info('  create-allowed  Create a new allowed setting value');
            $this->info('  show-all       Display all settings across all models');
            $this->info('  check-invalid  Check for invalid setting values');
            $this->info('  update-value   Update a specific value across all settings');
            $this->info('  show-allowed   Display all allowed setting values');
            $this->newLine();
            $this->info('Examples:');
            $this->info('  php artisan settings:manage create-allowed --key=display.theme --value=dark --caption="Dark Mode"');
            $this->info('  php artisan settings:manage update-value --key=display.theme --old-value=dark --new-value=light');

            return;
        }

        match ($this->argument('action')) {
            'create-allowed' => $this->createAllowedSetting(),
            'show-all' => $this->showAllSettings(),
            'check-invalid' => $this->checkInvalidSettings(),
            'update-value' => $this->updateSettingValue(),
            'show-allowed' => $this->showAllowedSettings(),
            default => $this->error('Invalid action. Run without action to see available options.')
        };
    }

    private function createAllowedSetting(): void
    {
        $key = $this->option('key');
        $value = $this->option('value');
        $caption = $this->option('caption');

        if (! $key || ! $value || ! $caption) {
            $this->error('Missing required options');

            return;
        }

        AllowedSettingValue::create([
            'setting_key' => $key,
            'value' => $value,
            'caption' => $caption,
        ]);

        $this->info("Created allowed setting: $key => $value ($caption)");
    }

    private function showAllSettings(): void
    {
        $settings = Setting::all();

        $rows = [];
        foreach ($settings as $setting) {
            foreach ($setting->values as $group => $groupSettings) {
                foreach ($groupSettings as $key => $value) {
                    $rows[] = [
                        $setting->model_type->value,
                        $setting->model_id,
                        "$group.$key",
                        is_array($value) ? json_encode($value) : $value,
                    ];
                }
            }
        }

        $this->table(
            ['Model Type', 'Model ID', 'Setting Key', 'Value'],
            $rows
        );
    }

    private function checkInvalidSettings(): void
    {
        $allowedSettings = AllowedSettingValue::pluck('value', 'setting_key')
            ->groupBy(fn ($value, $key) => explode('.', $key)[0]);

        $settings = Setting::all();
        $invalid = [];

        foreach ($settings as $setting) {
            foreach ($setting->values as $group => $groupSettings) {
                foreach ($groupSettings as $key => $value) {
                    $fullKey = "$group.$key";
                    if (isset($allowedSettings[$fullKey])) {
                        if (! in_array($value, $allowedSettings[$fullKey]->toArray())) {
                            $invalid[] = [
                                $setting->model_type->value,
                                $setting->model_id,
                                $fullKey,
                                $value,
                            ];
                        }
                    }
                }
            }
        }

        if (empty($invalid)) {
            $this->info('No invalid settings found');

            return;
        }

        $this->table(
            ['Model Type', 'Model ID', 'Setting Key', 'Invalid Value'],
            $invalid
        );
    }

    private function updateSettingValue(): void
    {
        $key = $this->option('key');
        $oldValue = $this->option('old-value');
        $newValue = $this->option('new-value');

        if (! $key || ! $oldValue || ! $newValue) {
            $this->error('Missing required options');

            return;
        }

        $settings = Setting::all();
        $updated = 0;

        foreach ($settings as $setting) {
            $values = $setting->values;
            $modified = false;

            foreach ($values as $group => &$groupSettings) {
                foreach ($groupSettings as $settingKey => &$value) {
                    if ("$group.$settingKey" === $key && $value === $oldValue) {
                        $value = $newValue;
                        $modified = true;
                        $updated++;
                    }
                }
            }

            if ($modified) {
                $setting->values = $values;
                $setting->save();
            }
        }

        $this->info("Updated $updated settings");
    }

    private function showAllowedSettings(): void
    {
        $allowedSettings = AllowedSettingValue::all();

        $rows = $allowedSettings->map(fn ($setting) => [
            $setting->setting_key,
            $setting->value,
            $setting->caption,
        ])->toArray();

        $this->table(
            ['Setting Key', 'Allowed Value', 'Caption'],
            $rows
        );
    }
}
