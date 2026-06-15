# LG Job X-Ray

**LG Job X-Ray** は、ハローワーク求人詳細ページのHTMLと、求人分析用のMarkdownプロンプトをZIP化するLaravel製Webツールです。

求人票の表面上の月給だけでは見えにくい、年間休日・労働時間・残業・固定残業代・賞与を踏まえた実質時給と、求人票の文言から考えられる企業側の課題仮説を、利用者自身のLLMで分析できる形に整えます。

## 公開URL

```text
https://xray.lazygenius.dev
```

## 目的

求人票を「月給」ではなく、**時間単価と労働条件の組み合わせ**で読み直すことが目的です。

ハローワーク求人詳細URLを入力すると、次の2ファイルをまとめたZIPを生成します。

```text
prompt.md
hellowork-detail.html
```

生成したZIPは、ChatGPT・Geminiなど、利用者が普段使っているLLMへそのままアップロードできます。

本ツール自身はLLM APIを呼び出しません。  
そのため、運営側に推論API料金が集中せず、利用者は無料版を含む任意のLLMを選べます。

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

## ZIPファイルの中身

```text
会社名_求人番号.zip
├─ prompt.md
└─ hellowork-detail.html
```

### `prompt.md`

ハローワーク求人票から、主に次の2点を一覧表で分析するためのAI用ハーネスです。

1. 実質時給・概算手取り時給
2. 求人票から見える企業側の課題仮説

主な出力項目：

- 額面年収
- 額面時給
- 残業込み額面時給
- 概算手取り年収
- 手取り時給
- 固定残業代を除いた参考時給
- 固定残業代・賞与・休日の注意点
- 求人票内の根拠表現
- 企業側の課題仮説
- 面接で確認すべき質問
- 求人票上の不明点

プロンプトは、過去会話・LLMメモリ・パーソナライズ・外部検索に依存せず、**同梱された求人HTMLだけを根拠に解析する独立解析モード**を前提としています。

### `hellowork-detail.html`

ハローワークインターネットサービスの求人詳細ページから取得したHTMLです。

Laravel側で求人票の全項目を完全にパースし続けるのではなく、生HTMLと分析ルールをLLMへ渡すことで、HTML構造変更や求人ごとの項目揺れに対するMVPの耐性を高めています。

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

生成されたZIPをChatGPTやGeminiなどへアップロードします。

LLMが `prompt.md` と `hellowork-detail.html` を読み取り、実質時給と企業課題仮説をMarkdown形式で出力します。

## 処理の流れ

```text
ハローワーク求人詳細URL
↓
公式ドメイン・詳細ページ・求人番号を検証
↓
求人詳細HTMLを取得
↓
事業所名を抽出
↓
prompt.md と HTML をZIP化
↓
利用者のブラウザへダウンロード
↓
サーバー上の一時ZIPを削除
↓
利用者が任意のLLMへアップロード
```

## MVPの設計判断

### BYO-LLM方式

本ツールはLLM APIを内蔵せず、利用者が自分のLLMを使用する方式です。

```text
BYO-LLM = Bring Your Own LLM
```

この方式には次の利点があります。

- 運営側にLLM API料金が発生しない
- APIキーをサーバーで管理する必要がない
- ChatGPT・Geminiなどを自由に選べる
- 無料版LLMでも利用できる
- 特定LLMの仕様変更や障害への依存を弱められる

### Laravel側の責務

Laravelは次の処理に集中します。

```text
入力検証
HTML取得
最低限の情報抽出
ファイル名の安全化
ZIP生成
ダウンロード
一時ファイル削除
```

求人票の意味理解・計算・課題仮説の生成は、AI用ハーネスと利用者のLLMへ任せます。

## セキュリティ・安定性

### 取得先URLの制限

次のホストだけを許可します。

```text
www.hellowork.mhlw.go.jp
```

求人詳細ページのパスも次に限定します。

```text
/kensaku/GECA110010.do
```

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

`ZipArchive`が見つからない場合は、使用中の`php.ini`でZIP拡張を有効化します。

```ini
extension=zip
```

### DOM

求人HTMLの最低限の解析にDOM拡張を使用します。

```bash
php -m | grep -E 'dom|libxml'
```

## テスト

```bash
php artisan test
```

GitHub Actionsでも、デプロイ前にPHPUnitを実行します。

## CI/CD

`main`ブランチへのpushを起点として、GitHub ActionsからXserverへ自動デプロイする構成です。

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

Laravel本体を公開フォルダの外へ配置し、`public`配下だけを公開します。

### 公開フォルダ同期

```bash
rsync -a \
  --delete \
  --exclude="index.php" \
  --exclude=".well-known/" \
  public/ \
  /home/xs227617/lazygenius.dev/public_html/xray.lazygenius.dev/
```

Xserver用に手動配置した`index.php`と、SSL認証等に使われる`.well-known`は同期対象から除外します。

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

## 注意事項

- 本ツールは求人比較のための補助ツールです
- 手取り年収・手取り時給は概算です
- 正確な税額や社会保険料を保証するものではありません
- 企業課題分析は断定ではなく、面接で確認するための仮説です
- LLMの出力内容を保証するものではありません
- 利用するLLMでは、可能であればメモリ・パーソナライズ・Web検索を無効にしてください
- ハローワーク側のHTML構造変更により、HTML取得や事業所名抽出に失敗する可能性があります
- ハローワーク側への過度な連続アクセスは行わないでください

## 今後の拡張候補

- HTML貼り付け方式への対応
- 複数求人の一括ZIP化
- `prompt.md`と`hellowork-detail.html`の個別ダウンロード
- AI用ハーネスの継続的な精度改善
- Laravel側計算ロジックの再整備
- 最低賃金との比較表示
- 求人比較用の構造化データ出力
- 解析結果の保存機能
- 複数求人の比較機能

## コンセプト

> この仕事は、あなたの時間をいくらで買おうとしているのか？

LG Job X-Rayは、求人票を会社側の説明として読むだけでなく、時間単価・拘束時間・固定残業代・休日数・賞与・現場リスクから読み解くためのツールです。
