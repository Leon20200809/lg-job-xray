<?php

use App\Services\HelloWork\HelloWorkHtmlFetcher;
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
    HelloWorkHtmlFetcher $fetcher
) {
    $validated = $request->validate([
        'url' => ['required', 'url'],
    ]);

    try {
        // URLを分解して求人番号 kJNo を取り出す。
        $jobNumber = $extractor->extract($validated['url']);

        // URLへアクセスしてHTMLを取得し、storage/app/hellowork-html に保存する。
        $result = $fetcher->fetchAndSave($validated['url'], $jobNumber);
    } catch (\Throwable $e) {
        return back()
            ->withErrors(['url' => $e->getMessage()])
            ->withInput();
    }

    return back()
        ->with('success', 'HTMLを取得して保存しました。')
        ->with('result', $result);
})->name('hellowork.fetch.store');