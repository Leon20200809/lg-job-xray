<?php
// HelloWorkHtmlFetcher.php
// → 指定URLへアクセスする
// → HTMLを取得する
// → storage/app/hellowork-html に保存する
// → 保存結果を返す

namespace App\Services\HelloWork;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class HelloWorkHtmlFetcher
{
    /**
     * ハローワーク求人詳細URLへアクセスしてHTMLを取得し、ローカルに保存する。
     *
     * @param string $url 求人詳細ページのURL
     * @param string $jobNumber 求人番号。保存ファイル名に使う。
     * @return array 保存結果
     */
    public function fetchAndSave(string $url, string $jobNumber): array
    {
        // ハローワーク側へHTTP GETリクエストを送る。
        $html = $this->fetchHtml($url);

        // 保存ディレクトリ。
        // storage/app/hellowork-html に保存する。
        $directory = storage_path('app/hellowork-html');

        // ディレクトリがなければ作成する。
        File::ensureDirectoryExists($directory);

        // 求人番号をファイル名にする。
        // 例：2702033704661.html
        $fileName = $jobNumber . '.html';

        // 実際の保存先パス。
        $filePath = $directory . DIRECTORY_SEPARATOR . $fileName;

        // HTMLをファイルとして保存する。
        File::put($filePath, $html);

        // 保存結果をControllerやRoute側へ返す。
        return [
            'job_number' => $jobNumber,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => number_format(File::size($filePath)) . ' bytes',
        ];
    }

    public function fetchHtml(string $url): string
    {
        // ハローワーク側へHTTP GETリクエストを送る。
        // timeout(15) は「15秒待っても返事がなければ失敗扱い」にする設定。
        $response = Http::timeout(15)
            ->withHeaders([
                // ブラウザっぽいUser-Agentを付ける。
                // 一部サイトはUser-Agentなしのアクセスを嫌うことがある。
                'User-Agent' => 'Mozilla/5.0 LG-Job-XRay/1.0',
            ])
            ->get($url);

        // HTTPステータスが 200番台 以外なら例外にする。
        if (! $response->successful()) {
            throw new \RuntimeException('HTMLの取得に失敗しました。HTTPステータス: ' . $response->status());
        }

        $html = $response->body();

        if ($html === '') {
            throw new \RuntimeException('取得したHTMLが空でした。');
        }

        return $html;
    }
}
