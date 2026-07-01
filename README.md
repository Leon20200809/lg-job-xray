# LG Job X-Ray

[![Deploy Laravel for Xserver](https://github.com/Leon20200809/lg-job-xray/actions/workflows/deploy.yml/badge.svg)](https://github.com/Leon20200809/lg-job-xray/actions/workflows/deploy.yml)

> この仕事は、あなたの時間をいくらで買おうとしているのか？

**LG Job X-Ray** は、ハローワーク求人詳細ページのHTMLと、求人分析用MarkdownプロンプトをZIP化するLaravel製Webツールです。

求人票の表面上の月給だけでは見えにくい、年間休日・労働時間・残業・固定残業代・賞与をもとに、**実質時給・概算手取り時給・企業課題仮説・採用ペルソナ**を、利用者自身のLLMで分析できる形に整えます。

本ツールはLLM APIを内蔵しません。  
Laravel側は「求人HTMLと分析指示書を安全にまとめる器」に徹し、意味理解・計算・仮説生成は、ChatGPT・Gemini・NotebookLMなど、利用者が普段使っているLLMへ渡します。

---

## 公開URL

```text
https://xray.lazygenius.dev
```

---

## このツールでできること

ハローワーク求人詳細URLを入力すると、以下の2ファイルを含むZIPを生成します。

```text
会社名_求人番号.zip
├─ prompt.md
└─ hellowork-detail.html
```

このZIPを任意のLLMへアップロードすると、求人票HTMLを根拠に、次のような分析Markdownを出力できます。

- 求人基本情報
- 額面年収・概算手取り年収
- 所定労働ベースの実質時給
- 残業込みの実質時給
- 固定残業代を除いた参考時給
- 固定残業代・賞与・休日条件の注意点
- 求人票から見える企業課題仮説
- 面接で確認すべき質問
- 企業が採用したい人物ペルソナ
- 求人票上の不明点

---

## 解決したい課題

求人票は「月給」だけを見ると判断を誤りやすいです。

```text
月給30万円
```

と書かれていても、実際には以下の条件で時間単価が大きく変わります。

- 年間休日数
- 1日の所定労働時間
- 月平均残業時間
- 固定残業代の有無
- 固定残業時間
- 賞与の有無・算定基準
- 基本給と手当の内訳
- 変形労働時間制・シフト制の有無

LG Job X-Ray は、求人票を「月給」ではなく、**時間単価と労働条件の組み合わせ**として読み直すためのツールです。

---

## 想定ワークフロー

```text
ハローワーク求人詳細URL
↓
LG Job X-Ray に入力
↓
prompt.md + hellowork-detail.html をZIP化
↓
ChatGPT / Gemini / NotebookLM などへアップロード
↓
LLMが求人票HTMLを解析
↓
Markdownレポートを出力
↓
Notionへ貼り付け、PDF化、面接前の確認資料として利用
```

---

## 主な機能

- ハローワーク求人詳細URLからHTMLを取得
- 公式ドメインと求人詳細ページのパスを検証
- URLから求人番号 `kJNo` を抽出
- 求人票HTMLから事業所名を抽出
- `会社名_求人番号.zip` 形式でダウンロード
- ZIP内に求人分析プロンプトと求人詳細HTMLを同梱
- 一時ZIPをUUID名で保存し、同時処理時のファイル競合を防止
- ダウンロード完了後にサーバー上の一時ZIPを削除
- 同一利用者からの連続リクエストをレート制限
- Laravel側で求人票の完全パースを抱え込まず、LLM用パック生成に責務を集中

---

## ZIPファイルの中身

```text
会社名_求人番号.zip
├─ prompt.md
└─ hellowork-detail.html
```

### `prompt.md`

ハローワーク求人票HTMLをLLMに読ませるための分析指示書です。

主な分析項目は以下です。

1. 実質時給・概算手取り時給
2. 固定残業代・賞与・休日条件の確認
3. 求人票から見える企業側の課題仮説
4. 面接で確認すべき質問
5. 企業が採用したい人物ペルソナ

プロンプトは、過去会話・LLMメモリ・パーソナライズ情報に依存せず、原則として**同梱された求人HTMLだけを根拠に解析する独立解析モード**を前提としています。

また、求人HTML内に次のリンクが存在する場合のみ、補助情報として公式企業HPを参照できるルールを含めています。

```html
<a id="ID_hp" href="https://example.com" target="_blank">https://example.com</a>
```

実際の解析では、例示URLではなく、求人票HTML内に存在する `id="ID_hp"` の `href` の実URLだけを参照対象にします。

### `hellowork-detail.html`

ハローワークインターネットサービスの求人詳細ページから取得したHTMLです。

Laravel側で求人票の全項目を完全にパースし続けるのではなく、**生HTMLと分析ルールをLLMへ渡す構成**にすることで、HTML構造変更や求人ごとの項目揺れに対するMVPの耐性を高めています。

---

## 使い方

### 1. トップページへアクセス

```text
https://xray.lazygenius.dev
```

### 2. ハローワーク求人詳細URLを入力

対象は、次の条件を満たす求人詳細ページです。

```text
https://www.hellowork.mhlw.go.jp/kensaku/GECA110010.do?...&kJNo=...
```

### 3. ZIPをダウンロード

ボタンを押すと、求人詳細HTMLと分析プロンプトを含むZIPが生成されます。

### 4. 任意のLLMへアップロード

生成されたZIPをChatGPT・Gemini・NotebookLMなどへアップロードします。

LLMが `prompt.md` と `hellowork-detail.html` を読み取り、実質時給・企業課題仮説・採用ペルソナをMarkdown形式で出力します。

---

## 処理の流れ

```text
ハローワーク求人詳細URL
↓
公式ドメインを検証
↓
求人詳細ページのパスを検証
↓
求人番号 kJNo を抽出
↓
求人詳細HTMLを取得
↓
事業所名を抽出
↓
prompt.md と hellowork-detail.html をZIP化
↓
利用者のブラウザへダウンロード
↓
サーバー上の一時ZIPを削除
↓
利用者が任意のLLMへアップロード
```

---

## MVPの設計判断

### BYO-LLM方式

本ツールはLLM APIを内蔵せず、利用者が自分のLLMを使用する方式です。

```text
BYO-LLM = Bring Your Own LLM
```

この方式には、次の利点があります。

- 運営側にLLM API料金が発生しない
- APIキーをサーバーで管理する必要がない
- ChatGPT・Gemini・NotebookLMなどを自由に選べる
- 無料版LLMでも利用できる
- 特定LLMの仕様変更や障害への依存を弱められる
- 同じZIPを複数LLMへ渡して出力比較できる

### Laravel側の責務

Laravelは、以下の処理に集中します。

```text
入力検証
HTML取得
求人番号抽出
事業所名抽出
ファイル名の安全化
ZIP生成
ダウンロード
一時ファイル削除
レート制限
```

求人票の意味理解・計算・課題仮説の生成は、AI用ハーネスと利用者のLLMへ任せます。

### LLM側の責務

LLMは、ZIP内の `prompt.md` と `hellowork-detail.html` を読み取り、以下を行います。

```text
求人票項目の抽出
年収・時給の計算
固定残業代の確認
賞与の扱いの整理
休日・労働時間の整理
企業課題仮説の生成
面接質問への変換
採用ペルソナの推定
```

---

## セキュリティ・安定性

### 取得先URLの制限

HTML取得先は、次のホストだけを許可します。

```text
www.hellowork.mhlw.go.jp
```

求人詳細ページのパスも次に限定します。

```text
/kensaku/GECA110010.do
```

これにより、任意URL取得によるSSRFリスクを抑えています。

### レート制限

ZIP生成ルートにはLaravelのThrottle Middlewareを設定しています。

```text
1分あたり5回
```

### ZIPファイルの競合防止

利用者向けのダウンロード名と、サーバー内部の一時ファイル名を分離しています。

```text
利用者向け：
会社名_求人番号.zip

サーバー内部：
UUID.zip
```

これにより、同じ求人が同時に処理された場合の上書き競合を防ぎます。

### 一時ファイル削除

ZIPはレスポンス送信後に自動削除します。

求人HTMLや生成ZIPを継続保存しないMVP構成です。

---

## 技術構成

- PHP 8.3
- Laravel 13
- Blade
- Tailwind CSS 4
- Vite 8
- ZipArchive
- DOMDocument / DOMXPath
- PHPUnit
- GitHub Actions
- Xserver
- Git / GitHub
- LLM向けMarkdownプロンプト

---

## 主要ディレクトリ

```text
.github/
└─ workflows/
   └─ deploy.yml

app/
└─ Services/
   └─ HelloWork/
      ├─ HelloWorkHtmlFetcher.php
      ├─ HelloWorkHtmlParser.php
      ├─ HelloWorkJobNumberExtractor.php
      └─ HelloWorkLlmPackBuilder.php

resources/
├─ css/
│  └─ app.css
├─ prompts/
│  └─ hellowork-wage-xray.md
└─ views/
   └─ xray/
      └─ llm-pack.blade.php

routes/
└─ web.php

tests/
├─ Feature/
└─ Unit/
```

---

## ローカル環境構築

### 必要環境

- PHP 8.3以上
- Composer
- Node.js
- npm
- PHP拡張 `zip`
- PHP拡張 `dom`
- SQLite、または任意のLaravel対応DB

### セットアップ

```bash
git clone https://github.com/Leon20200809/lg-job-xray.git
cd lg-job-xray

composer install
cp .env.example .env
php artisan key:generate

npm ci
npm run build

php artisan serve
```

---

## 必要なPHP拡張

### ZIP

ZIP生成にはPHP拡張 `zip` が必要です。

```bash
php -m | grep zip
```

Windowsの場合：

```bash
php -m | findstr zip
```

`ZipArchive` が見つからない場合は、使用中の `php.ini` でZIP拡張を有効化します。

```ini
extension=zip
```

### DOM

求人HTMLの最低限の解析にDOM拡張を使用します。

```bash
php -m | grep -E 'dom|libxml'
```

---

## テスト

```bash
php artisan test
```

GitHub Actionsでも、デプロイ前にPHPUnitを実行します。

---

## CI/CD

`main` ブランチへのpushを起点として、GitHub ActionsからXserverへ自動デプロイする構成です。

```text
mainへpush
↓
リポジトリをCheckout
↓
PHP 8.3と必要拡張を準備
↓
Composer依存関係をインストール
↓
Node.jsとnpm依存関係を準備
↓
Vite / Tailwind CSSを本番ビルド
↓
PHPUnit実行
↓
テスト成功時のみXserverへSSH接続
↓
Xserver上でgit pull
↓
Composer本番依存関係を更新
↓
Vite本番ビルド
↓
Laravelキャッシュを更新
↓
public配下を公開フォルダへrsync
```

### GitHub Actions Secrets

```text
SSH_PRIVATE_KEY
KNOWN_HOSTS
```

### Xserver上の配置

Laravel本体：

```text
/home/xs227617/laravel-apps/lg-job-xray
```

公開フォルダ：

```text
/home/xs227617/lazygenius.dev/public_html/xray.lazygenius.dev
```

Laravel本体を公開フォルダの外へ配置し、`public` 配下だけを公開します。

### 公開フォルダ同期

```bash
rsync -a \
  --delete \
  --exclude="index.php" \
  --exclude=".well-known/" \
  public/ \
  /home/xs227617/lazygenius.dev/public_html/xray.lazygenius.dev/
```

Xserver用に手動配置した `index.php` と、SSL認証等に使われる `.well-known` は同期対象から除外します。

---

## 本番環境の主な設定

```env
APP_NAME="LG Job X-Ray"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://xray.lazygenius.dev

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

本MVPはDBへ解析結果を保存しないため、セッションとキャッシュはファイル方式、キューは同期方式としています。

---

## 注意事項

- 本ツールは求人比較のための補助ツールです
- 手取り年収・手取り時給は概算です
- 正確な税額や社会保険料を保証するものではありません
- 企業課題分析は断定ではなく、面接で確認するための仮説です
- 採用ペルソナは求人票上の記載から推定する仮説です
- LLMの出力内容を保証するものではありません
- 利用するLLMでは、可能であればメモリ・パーソナライズ・Web検索を無効にしてください
- 公式企業HPの参照は、求人HTML内の `id="ID_hp"` のリンクがある場合のみ補助情報として扱います
- ハローワーク側のHTML構造変更により、HTML取得や事業所名抽出に失敗する可能性があります
- ハローワーク側への過度な連続アクセスは行わないでください

---

## 今後の拡張候補

- HTML貼り付け方式への対応
- 複数求人の一括ZIP化
- `prompt.md` と `hellowork-detail.html` の個別ダウンロード
- AI用ハーネスの継続的な精度改善
- 採用ペルソナ出力の精度改善
- 公式企業HPの補助参照ルール強化
- Laravel側計算ロジックの再整備
- 最低賃金との比較表示
- 求人比較用の構造化データ出力
- 解析結果の保存機能
- 複数求人の比較機能
- Notion貼り付け・PDF化しやすい出力テンプレート改善

---

## 設計思想

LG Job X-Ray は、求人票を企業側の説明として読むだけではなく、以下の観点から読み直すためのツールです。

```text
月給
↓
年収
↓
年間労働時間
↓
実質時給
↓
固定残業代の構造
↓
求人票内の言葉
↓
企業課題仮説
↓
面接で確認すべき質問
```

コード側で全てを抱え込むのではなく、Laravelは素材生成に集中し、LLMには求人票の意味理解を任せます。

```text
Laravel = 求人票を整える器
prompt.md = 分析の刃
LLM = 読解と仮説生成
利用者 = 最終判断者
```

求人票の表面を剥がし、時間単価・拘束時間・固定残業代・休日数・賞与・現場リスクから、応募前に確認すべき論点を可視化する。  
それが LG Job X-Ray の目的です。
