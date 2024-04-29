<?php

namespace App\Filament\Widgets;

use App\Models\Code;
use App\Models\Codebook;
use App\Models\Project;
use App\Models\Source;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $totalUploadedFileSizeInMb = Cache::remember('total_uploaded_file_size', now()->addMinutes(30), function () {
            $totalUploadedFileSize = Source::lazy()->reduce(function ($carry, $source) {
                $path = $source->upload_path;
                return $carry + (file_exists($path) ? filesize($path) : 0);
            }, 0);

            return number_format($totalUploadedFileSize / 1048576, 2) . ' MB';
        });
        $totalConvertedFileSizeInMb = Cache::remember('total_converted_file_size', now()->addMinutes(30), function () {
            $totalUploadedFileSize = Source::lazy()->reduce(function ($carry, $source) {
                $path = $source->converted->path;
                return $carry + (file_exists($path) ? filesize($path) : 0);
            }, 0);

            return number_format($totalUploadedFileSize / 1048576, 2) . ' MB';
        });

        return [
            Stat::make('Users', User::all()->count())->description('Total number of users')->descriptionIcon('heroicon-o-user-group'),
            Stat::make('Projects', Project::all()->count())->description('Total number of projects')->descriptionIcon('heroicon-o-rectangle-stack'),
            Stat::make('Documents', Source::all()->count())->description('Total number of documents')->descriptionIcon('heroicon-o-document'),
            Stat::make('Codebooks', Codebook::all()->count())->description('Total number of Codebooks')->descriptionIcon('heroicon-o-rectangle-stack'),
            Stat::make('Codes', Code::all()->count())->description('Total number of Codes')->descriptionIcon('heroicon-o-rectangle-stack'),
            Stat::make('Uploaded Files Size', $totalUploadedFileSizeInMb)->description('Total size of uploaded files')->descriptionIcon('heroicon-o-arrow-up-on-square'),
            Stat::make('Converted Files Size', $totalConvertedFileSizeInMb)->description('Total size of Converted files')->descriptionIcon('heroicon-o-arrow-up-on-square'),
        ];
    }
}
