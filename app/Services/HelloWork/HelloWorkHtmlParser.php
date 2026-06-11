<?php

namespace App\Services\HelloWork;

use Illuminate\Support\Facades\File;

class HelloWorkHtmlParser
{
    /**
     * 保存済みHTMLファイルを読み込み、求人票の主要項目を抽出する。
     *
     * @param string $filePath 保存済みHTMLファイルのフルパス
     * @return array 抽出した求人データ
     */
    public function parseFile(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new \RuntimeException('解析対象のHTMLファイルが見つかりません。');
        }

        $html = File::get($filePath);

        return $this->parseHtml($html);
    }

    /**
     * HTML文字列から求人票の主要項目を抽出する。
     *
     * Parserの責務は「HTMLから文字列を抜くこと」だけ。
     * 数値変換や時給計算は、次の Calculator に任せる。
     *
     * @param string $html ハローワーク求人詳細HTML
     * @return array 抽出した求人データ
     */
    public function parseHtml(string $html): array
    {
        $xpath = $this->createXPath($html);

        return [
            // 基本情報
            'job_number' => $this->textById($xpath, 'ID_kjNo'),
            'company_name' => $this->textById($xpath, 'ID_jgshMei'),
            'job_title' => $this->textById($xpath, 'ID_sksu'),

            // 賃金・手当
            'wage_range' => $this->textById($xpath, 'ID_chgn'),
            'base_salary_range' => $this->textById($xpath, 'ID_khky'),
            'wage_type' => $this->textById($xpath, 'ID_chgnKeitaiToKbn'),
            'monthly_work_days' => $this->textById($xpath, 'ID_thkinRodoNissu'),

            // 固定残業
            'fixed_overtime_status' => $this->textById($xpath, 'ID_koteiZngyKbn'),
            'fixed_overtime_amount' => $this->textById($xpath, 'ID_koteiZngy'),
            'fixed_overtime_note' => $this->textById($xpath, 'ID_koteiZngyTkjk'),

            // 賞与
            'bonus_status' => $this->textById($xpath, 'ID_shoyoSdNoUmu'),
            'bonus_previous_result' => $this->textById($xpath, 'ID_shoyoMaeNendoUmu'),

            // 労働時間
            'working_time_system' => $this->textById($xpath, 'ID_shgJn'),
            'flexible_working_time_unit' => $this->textById($xpath, 'ID_henkeiRdTani'),
            'working_time_1' => $this->textById($xpath, 'ID_shgJn1'),
            'monthly_overtime' => $this->textById($xpath, 'ID_thkinJkgiRodoJn'),
            'break_time' => $this->textById($xpath, 'ID_kyukeiJn'),
            'annual_holidays' => $this->textById($xpath, 'ID_nenkanKjsu'),

            // 休日
            'holiday' => $this->textById($xpath, 'ID_kyjs'),
            'weekly_two_days' => $this->textById($xpath, 'ID_shukFtskSei'),
        ];
    }

    /**
     * HTML文字列から DOMXPath を作る。
     *
     * DOMDocument:
     * HTMLをPHPで扱えるツリー構造に変換する。
     *
     * DOMXPath:
     * HTML内の特定要素を XPath で探すための道具。
     */
    private function createXPath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();

        // ハローワークHTMLは日本語を含むので、UTF-8として読み込ませる。
        // libxml_use_internal_errors(true) により、HTMLの細かい警告で処理を止めない。
        libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();

        return new \DOMXPath($dom);
    }

    /**
     * 指定した id を持つ要素のテキストを取得する。
     *
     * 例：
     * ID_chgn
     * ↓
     * 212,700円〜212,700円
     *
     * XPath:
     * //*[@id="ID_chgn"]
     *
     * 意味：
     * HTML内のどこでもいいので、id="ID_chgn" の要素を探す。
     */
    private function textById(\DOMXPath $xpath, string $id): ?string
    {
        $nodes = $xpath->query('//*[@id="' . $id . '"]');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $text = $nodes->item(0)?->textContent ?? '';

        return $this->normalizeText($text);
    }

    /**
     * HTMLから取った文字列を読みやすい形に整える。
     *
     * ハローワークHTMLには改行・タブ・全角スペース・余分な空白が混ざる。
     * ここでは最低限、連続する空白を1つにまとめる。
     */
    private function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 改行・タブ・連続スペースを1つの半角スペースにまとめる。
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}