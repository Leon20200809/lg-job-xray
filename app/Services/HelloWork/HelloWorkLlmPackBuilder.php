<?php

namespace App\Services\HelloWork;

use Illuminate\Support\Facades\File;
use ZipArchive;

class HelloWorkLlmPackBuilder
{
    public function build(string $html, ?string $companyName, string $jobNumber): array
    {
        $directory = storage_path('app/llm-packs');

        File::ensureDirectoryExists($directory);

        $safeCompanyName = $this->safeFileName($companyName ?: 'unknown-company');
        $safeJobNumber = $this->safeFileName($jobNumber);

        $zipFileName = "{$safeCompanyName}_{$safeJobNumber}.zip";
        $zipFilePath = $directory . DIRECTORY_SEPARATOR . $zipFileName;

        $promptPath = resource_path('prompts/hellowork-wage-xray.md');

        if (! File::exists($promptPath)) {
            throw new \RuntimeException('プロンプトファイルが見つかりません。');
        }

        $prompt = File::get($promptPath);

        $zip = new ZipArchive();

        $opened = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            throw new \RuntimeException('ZIPファイルの作成に失敗しました。');
        }

        $zip->addFromString('prompt.md', $prompt);
        $zip->addFromString('hellowork-detail.html', $html);

        $zip->close();

        return [
            'file_name' => $zipFileName,
            'file_path' => $zipFilePath,
        ];
    }

    private function safeFileName(string $value): string
    {
        $value = trim($value);

        $value = preg_replace('/[\/\\\\:\*\?"<>\|]/u', '_', $value) ?? $value;
        $value = preg_replace('/\s+/u', '_', $value) ?? $value;
        $value = trim($value, "._- \t\n\r\0\x0B");

        if ($value === '') {
            return 'unknown';
        }

        return mb_substr($value, 0, 80, 'UTF-8');
    }
}