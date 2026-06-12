<?php

use App\Services\HelloWork\HelloWorkHtmlFetcher;
use App\Services\HelloWork\HelloWorkHtmlParser;
use App\Services\HelloWork\HelloWorkJobNumberExtractor;
use App\Services\HelloWork\HelloWorkJobDataNormalizer;
use App\Services\HelloWork\WageEstimateCalculator;
use App\Services\HelloWork\EstimateResultFormatter;
use App\Services\HelloWork\HelloWorkLlmPackBuilder;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 最初の定義（後で上書きされて無効になる）
Route::get('/', function () {
    return view('welcome'); // ← 実際には使われない
})->name('xray.index');

// これが '/' を上書きして有効になる
Route::view('/', 'xray.llm-pack')->name('xray.index');

// '/xray/llm-pack' も同じビューを返す
Route::view('/xray/llm-pack', 'xray.llm-pack')
    ->name('xray.llm-pack.create');



Route::post('/xray', function (
    Request $request,
    HelloWorkJobNumberExtractor $extractor,
    HelloWorkHtmlFetcher $fetcher,
    HelloWorkHtmlParser $parser,
    HelloWorkJobDataNormalizer $normalizer,
    WageEstimateCalculator $calculator,
    EstimateResultFormatter $formatter
) {
    $validated = $request->validate([
        'url' => ['required', 'url'],
    ]);

    try {
        $jobNumber = $extractor->extract($validated['url']);

        // 保存せず、HTML文字列だけ取得する。
        $html = $fetcher->fetchHtml($validated['url']);

        $parsed = $parser->parseHtml($html);
        $normalized = $normalizer->normalize($parsed);
        $estimate = $calculator->calculate($normalized);

        $formattedEstimate = $formatter->format(
            $estimate,
            $normalized,
            1177,
            '大阪府'
        );

        $result = [
            'job_number' => $jobNumber,
            'parsed' => $parsed,
            'normalized' => $normalized,
            'estimate' => $estimate,
            'formatted_estimate' => $formattedEstimate,
        ];
    } catch (\Throwable $e) {
        return back()
            ->withErrors(['url' => $e->getMessage()])
            ->withInput();
    }

    return back()
        ->with('success', '求人情報を解析しました。')
        ->with('result', $result);
})->middleware('throttle:5,1')->name('xray.analyze');


Route::get('/hellowork/fetch', function () {
    return view('hellowork.fetch');
})->name('hellowork.fetch.create');

Route::post('/hellowork/fetch', function (
    Request $request,
    HelloWorkJobNumberExtractor $extractor,
    HelloWorkHtmlFetcher $fetcher,
    HelloWorkHtmlParser $parser,
    HelloWorkJobDataNormalizer $normalizer,
    WageEstimateCalculator $calculator,
    EstimateResultFormatter $formatter
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
        // 抽出した文字列を、計算しやすい数値データに変換する。
        $parsed = $parser->parseFile($result['file_path']);
        $normalized = $normalizer->normalize($parsed);

        // 正規化済みデータから賃金見積もりを計算する。
        $estimate = $calculator->calculate($normalized);

        // MVPでは大阪府最低賃金を仮設定する。
        $formattedEstimate = $formatter->format(
            $estimate,
            $normalized,
            1177,
            '大阪府'
        );


        // 保存結果の中に、抽出結果・正規化結果・計算結果を含める。
        $result['parsed'] = $parsed;
        $result['normalized'] = $normalized;
        $result['estimate'] = $estimate;
        $result['formatted_estimate'] = $formattedEstimate;
    } catch (\Throwable $e) {
        return back()
            ->withErrors(['url' => $e->getMessage()])
            ->withInput();
    }

    return back()
        ->with('success', 'HTMLを取得・保存し、求人情報を抽出しました。')
        ->with('result', $result);
})->name('hellowork.fetch.store');

// LLM投入ZIPパック
Route::get('/xray/llm-pack', function () {
    return view('xray.llm-pack');
})->name('xray.llm-pack.create');

Route::post('/xray/llm-pack', function (
    Request $request,
    HelloWorkJobNumberExtractor $extractor,
    HelloWorkHtmlFetcher $fetcher,
    HelloWorkHtmlParser $parser,
    HelloWorkLlmPackBuilder $packBuilder
) {
    $validated = $request->validate([
        'url' => ['required', 'url'],
    ]);

    try {
        $jobNumber = $extractor->extract($validated['url']);

        $html = $fetcher->fetchHtml($validated['url']);

        $parsed = $parser->parseHtml($html);

        $companyName = $parsed['company_name'] ?? null;

        $pack = $packBuilder->build(
            $html,
            $companyName,
            $jobNumber
        );
    } catch (\Throwable $e) {
        return back()
            ->withErrors(['url' => $e->getMessage()])
            ->withInput();
    }

    return response()
    ->download($pack['file_path'], $pack['file_name'], [
        'Content-Type' => 'application/zip',
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ])
    ->deleteFileAfterSend();
})->middleware('throttle:5,1')->name('xray.llm-pack.store');
