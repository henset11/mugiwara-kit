<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Mugiwara Kit');
        $this->migrator->add('general.site_logo', 'image/site-logo.webp');
        $this->migrator->add('general.dark_site_logo', 'image/dark-site-logo.webp');
        $this->migrator->add('general.favicon', 'image/favicon.ico');
        $this->migrator->add('general.site_active', true);
        $this->migrator->add('general.registration_enabled', true);
        $this->migrator->add('general.login_enabled', true);
        $this->migrator->add('general.password_reset_enabled', true);
        $this->migrator->add('general.sso_enabled', true);
    }
};
