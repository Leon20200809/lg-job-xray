{{-- fetch.blade.php
→ URL入力フォームを表示する
→ 保存結果を表示する
→ エラーを表示する --}}
@php
    $result = session('result');
@endphp

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>LG Job X-Ray | 求人詳細HTML保存</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-3xl px-5 py-12">
        <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="mb-2 text-sm font-bold tracking-wider text-red-800">
                LG Job X-Ray
            </p>

            <h1 class="text-3xl font-bold tracking-tight text-slate-950">
                求人詳細HTML保存
            </h1>

            <p class="mt-4 leading-8 text-slate-600">
                ハローワーク求人詳細ページのURLを貼り付けると、
                HTMLを取得して保存します。
            </p>

            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-800">
                    この仕事は、あなたの時間をいくらで買おうとしているのか？
                </p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    まずは求人票HTMLを保存し、後続の解析処理で実質時給を可視化します。
                </p>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
                    <p class="font-bold">入力内容を確認してください。</p>

                    <ul class="mt-2 list-disc pl-5 text-sm leading-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($result)
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('hellowork.fetch.store') }}" class="mt-8">
                @csrf

                <div>
                    <label for="url" class="block text-sm font-bold text-slate-800">
                        ハローワーク求人詳細URL
                    </label>

                    <input id="url" type="url" name="url" value="{{ old('url') }}"
                        placeholder="https://www.hellowork.mhlw.go.jp/kensaku/GECA110010.do?..." required
                        class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base shadow-sm outline-none transition focus:border-red-800 focus:ring-2 focus:ring-red-800/20">

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        対象はハローワークインターネットサービスの求人詳細ページです。
                        URL内に <code class="rounded bg-slate-100 px-1 py-0.5 text-slate-700">kJNo</code> が含まれている必要があります。
                    </p>
                </div>

                <div class="mt-6">
                    <button type="submit"
                        class="cursor-pointer inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-base font-bold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-950/30">
                        HTMLを取得して保存する
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <h2 class="text-lg font-bold text-slate-950">
                        保存結果
                    </h2>

                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-sm font-bold text-slate-700">求人番号</dt>
                            <dd class="mt-1 break-all text-slate-950">
                                {{ $result['job_number'] ?? '不明' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-bold text-slate-700">保存ファイル名</dt>
                            <dd class="mt-1 break-all text-slate-950">
                                {{ $result['file_name'] ?? '不明' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-bold text-slate-700">保存先</dt>
                            <dd class="mt-1 break-all text-slate-950">
                                {{ $result['file_path'] ?? '不明' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-bold text-slate-700">ファイルサイズ</dt>
                            <dd class="mt-1 break-all text-slate-950">
                                {{ $result['file_size'] ?? '不明' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif

            @php
                $labels = [
                    'job_number' => '求人番号',
                    'company_name' => '事業所名',
                    'job_title' => '職種',
                    'wage_range' => '賃金',
                    'base_salary_range' => '基本給',
                    'wage_type' => '賃金形態',
                    'monthly_work_days' => '月平均労働日数',
                    'fixed_overtime_status' => '固定残業代の有無',
                    'fixed_overtime_amount' => '固定残業代',
                    'fixed_overtime_note' => '固定残業代に関する特記事項',
                    'bonus_status' => '賞与制度の有無',
                    'bonus_previous_result' => '賞与前年度実績',
                    'working_time_system' => '就業時間制度',
                    'flexible_working_time_unit' => '変形労働時間制の単位',
                    'working_time_1' => '就業時間1',
                    'monthly_overtime' => '月平均時間外労働時間',
                    'break_time' => '休憩時間',
                    'annual_holidays' => '年間休日数',
                    'holiday' => '休日',
                    'weekly_two_days' => '週休二日制',
                ];
            @endphp

            @if (!empty($result['parsed']))
                <div class="mt-8 rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="text-lg font-bold text-slate-950">抽出結果</h2>

                    <dl class="mt-4 space-y-4">
                        @foreach ($result['parsed'] as $key => $value)
                            <div>
                                <dt class="text-sm font-bold text-slate-700">
                                    {{ $labels[$key] ?? $key }}
                                </dt>
                                <dd class="mt-1 break-all text-slate-950">
                                    {{ $value ?? '未取得' }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if (!empty($result['normalized']))
                <div class="mt-8 rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="text-lg font-bold text-slate-950">正規化結果</h2>

                    <dl class="mt-4 space-y-4">
                        @foreach ($result['normalized'] as $key => $value)
                            <div>
                                <dt class="text-sm font-bold text-slate-700">
                                    {{ $key }}
                                </dt>
                                <dd class="mt-1 break-all text-slate-950">
                                    {{ is_null($value) ? '未取得' : $value }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if (!empty($result['estimate']))
                <div class="mt-8 rounded-xl border border-red-200 bg-red-50 p-5">
                    <h2 class="text-lg font-bold text-slate-950">賃金見積もり</h2>

                    <dl class="mt-4 space-y-4">
                        @foreach ($result['estimate'] as $key => $value)
                            <div>
                                <dt class="text-sm font-bold text-slate-700">
                                    {{ $key }}
                                </dt>
                                <dd class="mt-1 break-all text-slate-950">
                                    {{ is_null($value) ? '未取得' : $value }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if (!empty($result['formatted_estimate']))
                <div class="mt-8 rounded-2xl border border-red-200 bg-red-50 p-6">
                    <p class="mb-2 text-sm font-bold tracking-wider text-red-800">
                        Wage X-Ray
                    </p>

                    <h2 class="text-2xl font-bold text-slate-950">
                        {{ $result['formatted_estimate']['title'] }}
                    </h2>

                    <p class="mt-4 leading-8 text-slate-700">
                        {{ $result['formatted_estimate']['summary'] }}
                    </p>

                    @if (!empty($result['formatted_estimate']['items']))
                        <dl class="mt-6 grid gap-4">
                            @foreach ($result['formatted_estimate']['items'] as $item)
                                <div class="rounded-xl border border-slate-200 bg-white p-4">
                                    <dt class="text-sm font-bold text-slate-600">
                                        {{ $item['label'] }}
                                    </dt>
                                    <dd class="mt-1 text-xl font-bold text-slate-950">
                                        {{ $item['value'] }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    @if (!empty($result['formatted_estimate']['alerts']))
                        <div class="mt-6 space-y-3">
                            @foreach ($result['formatted_estimate']['alerts'] as $alert)
                                <div class="rounded-xl border bg-white p-4">
                                    <p class="text-sm font-bold text-slate-700">
                                        {{ $alert['label'] }}
                                    </p>

                                    <p class="mt-2 leading-7 text-slate-700">
                                        {{ $alert['message'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($result['formatted_estimate']['notes']))
                        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4">
                            <p class="text-sm font-bold text-slate-700">
                                補足
                            </p>

                            <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                                @foreach ($result['formatted_estimate']['notes'] as $note)
                                    <li>{{ $note }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </main>
</body>

</html>
