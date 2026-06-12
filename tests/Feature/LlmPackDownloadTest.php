<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\TestDox;

class LlmPackDownloadTest extends TestCase
{
    #[TestDox('トップページにLLM解析ZIP作成画面が表示されることを確認する')]
    public function test_root_page_is_displayed(): void
    {
        // HTTPステータス 200
        $response = $this->get('/');
        $response->assertStatus(200);

        // 画面内に「LLM解析ZIPを作成する」がある
        $response->assertSee('LLM解析ZIPを作成する');

        // 画面内に「LLM解析ZIPをダウンロードする」がある
        $response->assertSee('LLM解析ZIPをダウンロードする');
    }

    #[TestDox('/xray/llm-packでLLM解析ZIP作成画面のビューを返すことを確認する')]
    public function test_llm_pack_page_is_displayed(): void
    {
        $response = $this->get('/xray/llm-pack');

        $response->assertStatus(200);
        $response->assertViewIs('xray.llm-pack');
    }

    /**
     * トップページにLLM解析ZIP作成画面が表示され、
     * 見出しがh1、ダウンロード実行要素がsubmitボタンとして存在することを確認する。
     */
    public function test_llm_pack_page_has_heading_and_download_button(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $xpath = $this->makeXPath($response->getContent());

        $heading = $xpath->query('//h1[contains(normalize-space(.), "LLM解析ZIPを作成する")]');

        $this->assertSame(
            1,
            $heading->length,
            '「LLM解析ZIPを作成する」がh1として存在しません。'
        );

        $button = $xpath->query('//button[@type="submit" and contains(normalize-space(.), "LLM解析ZIPをダウンロードする")]');

        $this->assertSame(
            1,
            $button->length,
            '「LLM解析ZIPをダウンロードする」がsubmitボタンとして存在しません。'
        );
    }

    /**
     * レスポンスHTMLをDOMXPathで検査できる形に変換する。
     */
    private function makeXPath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();

        libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();

        return new \DOMXPath($dom);
    }
}
