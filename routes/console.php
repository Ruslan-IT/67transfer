<?php

use App\Models\Enquiry;
use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('enquiries:purge', function () {
    $days = Setting::get('personal_data.retention_days');

    if (! is_string($days) || preg_match('/^\d+$/', $days) !== 1 || (int) $days < 1) {
        $this->comment('Срок хранения не задан. Заявки не удалялись.');

        return;
    }

    $count = Enquiry::query()->where('created_at', '<', now()->subDays((int) $days))->delete();
    $this->info('Удалено заявок: '.$count);
})->purpose('Удалить заявки старше срока, заданного оператором');

Schedule::command('enquiries:purge')->daily();
