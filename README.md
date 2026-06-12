# LG Job X-Ray

LG Job X-Ray は、ハローワーク求人詳細ページのHTMLと、実質時給算出用プロンプトをZIP化するLaravel製ツールです。

求人票の表面上の月給・時給だけでなく、年間休日、労働時間、残業時間、固定残業代、賞与、求人票の表現から見える現場リスクを、LLMで読み解くための解析パックを作成します。

## 目的

求人票を「月給」ではなく「時間単価」で読み直すことを目的としています。

このツールでは、ハローワーク求人詳細URLから求人詳細HTMLを取得し、以下の2ファイルをまとめたZIPを生成します。

```text
prompt.md
hellowork-detail.html
```

生成したZIPファイルは、そのままChatGPT、GeminiなどのLLMにアップロードして使います。

## 主な機能

* ハローワーク求人詳細URLからHTMLを取得
* 求人番号を抽出
* 求人票HTMLから事業所名を抽出
* `会社名_求人番号.zip` 形式でZIPファイルを生成
* ZIP内に解析用プロンプトと求人詳細HTMLを同梱
* LLMにアップロードするだけで、実質時給・手取り時給・求人票リスクを分析可能

## ZIPファイルの中身

```text
会社名_求人番号.zip
├─ prompt.md
└─ hellowork-detail.html
```

### prompt.md

ハローワーク求人票から以下を読み解くためのプロンプトです。

* 額面年収
* 額面時給
* 概算手取り年収
* 手取り時給
* 固定残業代の注意点
* 賞与の確実性
* 求人票表現から見える会社側の課題仮説
* 面接で確認すべき質問
* 応募判断メモ

### hellowork-detail.html

ハローワークインターネットサービスの求人詳細ページHTMLです。

Laravel側で完全にパース・計算しきるのではなく、HTMLとプロンプトをLLMへ渡すことで、ハローワークHTMLの構造変更や項目揺れに強いMVP構成にしています。

## 使い方

### 1. トップページへアクセス

```text
/
```

または、

```text
/xray/llm-pack
```

### 2. ハローワーク求人詳細URLを入力

対象は、URL内に `kJNo` が含まれるハローワーク求人詳細ページです。

### 3. ZIPをダウンロード

ボタンを押すと、求人詳細HTMLとプロンプトを含むZIPファイルが生成されます。

### 4. LLMにアップロード

生成されたZIPファイルを、そのままChatGPTやGeminiなどのLLMにアップロードします。

LLMはZIP内の `prompt.md` と `hellowork-detail.html` を読み取り、実質時給・手取り時給・求人票から見える課題をMarkdown形式で出力します。

## 現在のMVP方針

現在の主導線は、Laravel側で完全自動計算する方式ではなく、LLM解析ZIPを生成する方式です。

```text
求人詳細URL
↓
HTML取得
↓
prompt.md と HTML をZIP化
↓
LLMにアップロード
↓
実質時給・手取り時給・求人票リスクを分析
```

手動計算ロジック、HTMLパーサ、正規化処理、Laravel側の時給計算処理は実験資産として残していますが、現時点のMVPでは主機能から外しています。

## 技術構成

* Laravel
* PHP
* Blade
* Tailwind CSS
* ZipArchive
* ハローワーク求人詳細HTML
* LLM向けMarkdownプロンプト

## 主要ディレクトリ

```text
app/
└─ Services/
   └─ HelloWork/
      ├─ HelloWorkHtmlFetcher.php
      ├─ HelloWorkHtmlParser.php
      ├─ HelloWorkJobNumberExtractor.php
      └─ HelloWorkLlmPackBuilder.php

resources/
├─ prompts/
│  └─ hellowork-wage-xray.md
└─ views/
   └─ xray/
      └─ llm-pack.blade.php

routes/
└─ web.php
```

## 必要なPHP拡張

ZIP生成にPHP拡張 `zip` が必要です。

ローカル環境で以下のエラーが出る場合があります。

```text
Class "ZipArchive" not found
```

その場合は、使用中の `php.ini` で以下を有効化します。

```ini
extension=zip
```

確認コマンド：

```bash
php -m | findstr zip
```

または、

```bash
php -m | grep zip
```

## 注意事項

* 本ツールは求人比較用の補助ツールです。
* 手取り年収・手取り時給は概算です。
* 正確な税額計算や社会保険料計算を保証するものではありません。
* 求人票から見える会社側の課題分析は断定ではなく、面接で確認するための仮説です。
* ハローワーク側のHTML構造変更により、会社名や求人番号の抽出に失敗する可能性があります。

## 今後の拡張候補

* HTML貼り付け方式への対応
* 複数求人の一括ZIP化
* Copilot向けに `prompt.md` と `hellowork-detail.html` を個別ダウンロード
* Laravel側での自動時給計算ロジック再整備
* 最低賃金との比較表示
* 求人票リスクスコアの自動算出
* 解析結果の保存機能
* 応募判断メモの蓄積機能

## コンセプト

この仕事は、あなたの時間をいくらで買おうとしているのか？

LG Job X-Ray は、求人票を「会社に都合のいい文章」として読むのではなく、時間単価・拘束時間・固定残業代・休日数・現場リスクから読み解くためのツールです。
