<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\HelloWork\HelloWorkJobNumberExtractor;

Route::get('/', function () {
    return redirect()->route('hellowork.fetch.create');
});

Route::get('/hellowork/fetch', function () {
    return view('hellowork.fetch');
})->name('hellowork.fetch.create');


Route::post('/hellowork/fetch', function (
    Request $request,
    HelloWorkJobNumberExtractor $extractor
) {
    $validated = $request->validate([
        'url' => ['required', 'url'],
    ]);

    try {
        $jobNumber = $extractor->extract($validated['url']);
    } catch (\InvalidArgumentException $e) {
        return back()
            ->withErrors(['url' => $e->getMessage()])
            ->withInput();
    }

    return back()
        ->with('success', 'URLチェック成功。次はHTML保存処理を接続します。')
        ->with('result', [
            'job_number' => $jobNumber,
            'file_name' => $jobNumber . '.html',
            'file_path' => 'storage/app/hellowork-html/' . $jobNumber . '.html',
            'file_size' => '未保存',
        ]);
})->name('hellowork.fetch.store');
