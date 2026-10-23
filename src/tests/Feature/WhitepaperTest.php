<?php

namespace Tests\Feature;

use Tests\TestCase;

class WhitepaperTest extends TestCase
{
    /**
     * トップページに技術白書へのリンクが存在することを検証
     */
    public function test_welcome_page_contains_whitepaper_links(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('/whitepaper');
        $response->assertSee('技術白書');
    }

    /**
     * /whitepaper (HTML版) が正常にレスポンスを返すことを検証
     */
    public function test_whitepaper_html_endpoint(): void
    {
        $response = $this->get('/whitepaper');
        $response->assertStatus(200);
        $response->assertSee('局所完結型サプライチェーンシナプスによる自律分散型B2B取引アーキテクチャ');
        $response->assertSee('MESHu-Whitepaper-v1.0.pdf');
    }

    /**
     * PDFファイルおよび sitemap.xml, robots.txt が public に実在することを検証
     */
    public function test_static_assets_exist(): void
    {
        $this->assertFileExists(public_path('docs/MESHu-Whitepaper-v1.0.pdf'));
        $this->assertGreaterThan(100000, filesize(public_path('docs/MESHu-Whitepaper-v1.0.pdf')));

        $this->assertFileExists(public_path('sitemap.xml'));
        $sitemapContent = file_get_contents(public_path('sitemap.xml'));
        $this->assertStringContainsString('https://meshu.org/whitepaper', $sitemapContent);
        $this->assertStringContainsString('https://meshu.org/docs/MESHu-Whitepaper-v1.0.pdf', $sitemapContent);

        $this->assertFileExists(public_path('robots.txt'));
        $robotsContent = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Sitemap: https://meshu.org/sitemap.xml', $robotsContent);
    }
}
