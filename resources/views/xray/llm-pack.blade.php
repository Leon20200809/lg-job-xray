<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>LG Job X-Ray | LLM解析ZIP作成</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-3xl px-5 py-12">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:p-10">
            <p class="mb-3 text-sm font-bold tracking-widest text-red-800">
                LG Job X-Ray
            </p>

            <h1 class="text-3xl font-black tracking-tight text-slate-950 md:text-5xl">
                LLM解析ZIPを作成する
            </h1>

            <p class="mt-5 leading-8 text-slate-600">
                ハローワーク求人詳細URLを入力すると、求人詳細HTMLと実質時給算出プロンプトをまとめたZIPファイルを作成します。
                作成したZIPは、そのままChatGPTなどのLLMにアップロードして使えます。
            </p>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-sm font-bold text-slate-800">
                    ZIPの中身
                </p>

                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-600">
                    <li>prompt.md</li>
                    <li>hellowork-detail.html</li>
                </ul>
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

            <form method="POST" action="{{ route('xray.llm-pack.store') }}" class="mt-8"
                onsubmit="
                    const button = this.querySelector('button[type=submit]');
                    button.disabled = true;
                    button.textContent = 'ZIP作成中...';
                ">
                @csrf

                <label for="url" class="block text-sm font-bold text-slate-800">
                    ハローワーク求人詳細URL
                </label>

                <input id="url" type="url" name="url" value="{{ old('url') }}"
                    placeholder="https://www.hellowork.mhlw.go.jp/kensaku/GECA110010.do?..."
                    required
                    class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-4 text-base shadow-sm outline-none transition focus:border-red-800 focus:ring-2 focus:ring-red-800/20">

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    URL内に
                    <code class="rounded bg-slate-100 px-1 py-0.5 text-slate-700">kJNo</code>
                    が含まれている求人詳細ページが対象です。
                </p>

                <button type="submit"
                    class="mt-6 inline-flex cursor-pointer items-center justify-center rounded-2xl bg-slate-950 px-6 py-4 text-base font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                    LLM解析ZIPをダウンロードする
                </button>
            </form>
        </section>
    </main>
</body>

</html>