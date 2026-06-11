<?php
// HelloWorkJobNumberExtractor.php
// → URLから kJNo を抜く
// → ファイル名用の求人番号を返す

namespace App\Services\HelloWork;

class HelloWorkJobNumberExtractor
{
    /**
     * ハローワーク求人詳細URLから求人番号 kJNo を取り出す。
     *
     * 例：
     * https://www.hellowork.mhlw.go.jp/kensaku/GECA110010.do?...&kJNo=2702033704661&...
     *
     * この中の kJNo=2702033704661 を取得して、
     * HTML保存時のファイル名などに使う。
     */
    public function extract(string $url): string
    {
        // URLを host / path / query などの部品に分解する。
        // 例：
        // host  => www.hellowork.mhlw.go.jp
        // path  => /kensaku/GECA110010.do
        // query => screenId=...&kJNo=2702033704661&...
        $parts = parse_url($url);

        // URLとして正しく分解できなかった場合は例外にする。
        if ($parts === false) {
            throw new \InvalidArgumentException('URLを解析できませんでした。');
        }

        // host はドメイン部分。
        // 今回はハローワーク公式ドメインだけ許可する。
        $host = $parts['host'] ?? '';

        if ($host !== 'www.hellowork.mhlw.go.jp') {
            throw new \InvalidArgumentException('ハローワークインターネットサービスのURLを入力してください。');
        }

        // path はドメインより後ろ、? より前の部分。
        // 求人詳細ページは /kensaku/GECA110010.do を想定する。
        $path = $parts['path'] ?? '';

        if ($path !== '/kensaku/GECA110010.do') {
            throw new \InvalidArgumentException('ハローワーク求人詳細ページのURLを入力してください。');
        }

        // query は ? より後ろの文字列。
        // 例：screenId=GECA110010&action=dispDetailBtn&kJNo=2702033704661
        $query = $parts['query'] ?? '';

        // query文字列をPHPの配列に変換する。
        // 例：
        // kJNo=2702033704661
        // ↓
        // $queryParams['kJNo'] = '2702033704661'
        parse_str($query, $queryParams);

        // kJNo は求人番号として使う重要パラメータ。
        // これがないURLは、どの求人詳細ページか判断できない。
        $jobNumber = $queryParams['kJNo'] ?? '';

        if ($jobNumber === '') {
            throw new \InvalidArgumentException('URL内に求人番号 kJNo が含まれていません。');
        }

        return $jobNumber;
    }
}