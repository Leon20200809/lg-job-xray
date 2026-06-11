<?php

use App\Services\HelloWork\HelloWorkHtmlFetcher;
use App\Services\HelloWork\HelloWorkHtmlParser;
use App\Services\HelloWork\HelloWorkJobNumberExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('hellowork.fetch.create');
});

Route::get('/hellowork/fetch', function () {
    return view('hellowork.fetch');
})->name('hellowork.fetch.create');

Route::post('/hellowork/fetch', function (
    Request $request,
    HelloWorkJobNumberExtractor $extractor,
    HelloWorkHtmlFetcher $fetcher,
    HelloWorkHtmlParser $parser
) {
    $validated = $request->validate([
        'url' => ['required', 'url'],
    ]);

    try {
        // URLから求人番号 kJNo を抽出する。
        $jobNumber = $extractor->extract($validated['url']);

        // URLへアクセスしてHTMLを取得し、storage/app/hellowork-html に保存する。
        $result = $fetcher->fetchAndSave($validated['url'], $jobNumber);

        // 保存したHTMLを読み込み、求人票の主要項目を抽出する。
        $parsed = $parser->parseFile($result['file_path']);

        // 保存結果の中に、抽出結果も含める。
        $result['parsed'] = $parsed;
    } catch (\Throwable $e) {
        return back()
            ->withErrors(['url' => $e->getMessage()])
            ->withInput();
    }

    return back()
        ->with('success', 'HTMLを取得・保存し、求人情報を抽出しました。')
        ->with('result', $result);
})->name('hellowork.fetch.store');