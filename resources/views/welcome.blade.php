@php
    $result = session('result');
    $formatted = $result['formatted_estimate'] ?? null;
    $normalized = $result['normalized'] ?? null;
@endphp

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>LG Job X-Ray | 求人票の時給換算</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-4xl px-5 py-10">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-10">
            <p class="mb-3 text-sm font-bold tracking-widest text-red-800">
                LG Job X-Ray
            </p>

            <h1 class="text-3xl font-black tracking-tight text-slate-950 md:text-5xl">
                求人票を、時給で読む。
            </h1>

            <p class="mt-5 max-w-2xl leading-8 text-slate-600">
                ハローワーク求人詳細URLを入力すると、求人票HTMLを解析し、月給求人は時給換算、
                時給求人は月収・年収目安として表示します。
            </p>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-sm font-bold text-slate-800">
                    この仕事は、あなたの時間をいくらで買おうとしているのか？
                </p>
                <p class="mt-2 text-sm leading-7 text-slate-600">
                    額面時給、概算手取り時給、最低賃金との比較を、応募判断に使いやすい形で表示します。
                </p>
            </div>

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800">
                    <p class="font-bold">入力内容を確認してください。</p>

                    <ul class="mt-2 list-disc pl-5 text-sm leading-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('xray.analyze') }}" class="mt-8"
                onsubmit="
                    const button = this.querySelector('button[type=submit]');
                    button.disabled = true;
                    button.textContent = '解析中...';
                ">
                @csrf

                <label for="url" class="block text-sm font-bold text-slate-800">
                    ハローワーク求人詳細URL
                </label>

                <input id="url" type="url" name="url" value="{{ old('url') }}"
                    placeholder="https://www.hellowork.mhlw.go.jp/kensaku/GECA110010.do?..." required
                    class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-4 text-base shadow-sm outline-none transition focus:border-red-800 focus:ring-2 focus:ring-red-800/20">

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    URL内に <code class="rounded bg-slate-100 px-1 py-0.5 text-slate-700">kJNo</code> が含まれている求人詳細ページが対象です。
                </p>

                <button type="submit"
                    class="mt-6 inline-flex cursor-pointer items-center justify-center rounded-2xl bg-slate-950 px-6 py-4 text-base font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                    求人票を解析する
                </button>
            </form>
        </section>

        @if (!empty($normalized))
            <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-8">
                <p class="text-sm font-bold tracking-widest text-slate-500">
                    JOB SUMMARY
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-950">
                    {{ $normalized['job_title'] ?? '求人情報' }}
                </h2>

                <dl class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <dt class="text-sm font-bold text-slate-500">事業所名</dt>
                        <dd class="mt-1 font-bold text-slate-950">
                            {{ $normalized['company_name'] ?? '未取得' }}
                        </dd>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <dt class="text-sm font-bold text-slate-500">賃金形態</dt>
                        <dd class="mt-1 font-bold text-slate-950">
                            {{ $normalized['wage_type'] ?? '未取得' }}
                        </dd>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <dt class="text-sm font-bold text-slate-500">求人番号</dt>
                        <dd class="mt-1 font-bold text-slate-950">
                            {{ $normalized['job_number'] ?? $result['job_number'] ?? '未取得' }}
                        </dd>
                    </div>
                </dl>
            </section>
        @endif

        @if (!empty($formatted))
            <section class="mt-8 rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm md:p-8">
                <p class="text-sm font-bold tracking-widest text-red-800">
                    WAGE X-RAY
                </p>

                <h2 class="mt-2 text-2xl font-black text-slate-950">
                    {{ $formatted['title'] }}
                </h2>

                <p class="mt-4 leading-8 text-slate-700">
                    {{ $formatted['summary'] }}
                </p>

                @if (!empty($formatted['items']))
                    <dl class="mt-6 grid gap-4 md:grid-cols-2">
                        @foreach ($formatted['items'] as $item)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <dt class="text-sm font-bold text-slate-500">
                                    {{ $item['label'] }}
                                </dt>
                                <dd class="mt-2 text-2xl font-black text-slate-950">
                                    {{ $item['value'] }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                @if (!empty($formatted['alerts']))
                    <div class="mt-6 space-y-3">
                        @foreach ($formatted['alerts'] as $alert)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
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

                @if (!empty($formatted['notes']))
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="text-sm font-bold text-slate-700">
                            補足
                        </p>

                        <ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                            @foreach ($formatted['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>
        @endif
    </main>
</body>

</html>