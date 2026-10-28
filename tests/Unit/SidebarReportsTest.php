<?php

namespace Tests\Unit;

use App\Support\SidebarReports;
use PHPUnit\Framework\TestCase;

class SidebarReportsTest extends TestCase
{
    public function test_sections_follow_the_report_url(): void
    {
        $this->assertSame('sales', SidebarReports::sectionFor('https://qpos.test/reports/profit-loss'));
        $this->assertSame('sales', SidebarReports::sectionFor('/reports/product-purchase-report'));
        $this->assertSame('sales', SidebarReports::sectionFor('/reports/product-sell-report'));
        $this->assertSame('sales', SidebarReports::sectionFor('/reports/gst-sales-report'));
        $this->assertSame('customers', SidebarReports::sectionFor('/reports/customer-supplier'));
        $this->assertSame('customers', SidebarReports::sectionFor('/reports/loyalty-report'));
        $this->assertSame('stock', SidebarReports::sectionFor('/reports/stock-adjustment-report'));
        $this->assertSame('stock', SidebarReports::sectionFor('/reports/items-report'));
        $this->assertSame('operations', SidebarReports::sectionFor('/reports/sales-representative-report'));
        $this->assertSame('operations', SidebarReports::sectionFor('/cheque/report'));
        $this->assertSame('operations', SidebarReports::sectionFor('/nutmeg/reports'));
        $this->assertSame('other', SidebarReports::sectionFor('/reports/custom-future-report'));
    }

    public function test_unknown_sidebar_items_are_kept(): void
    {
        $groups = SidebarReports::groupLinks([
            ['title' => 'Profit / Loss Report', 'url' => '/reports/profit-loss', 'icon' => '', 'active' => false],
            ['title' => 'Future Report', 'url' => '/reports/custom-future-report', 'icon' => '<svg></svg>', 'active' => false],
        ]);

        $titles = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $titles[] = $item['title'];
            }
        }

        $this->assertSame(['Profit / Loss Report', 'Future Report'], $titles);
        $this->assertSame('other', $groups[1]['key']);
    }

    public function test_only_the_sidebar_reports_dropdown_is_used(): void
    {
        $reports = new SidebarMenuItem('Reports', [
            $this->child('Profit / Loss Report', 'https://qpos.test/reports/profit-loss'),
            $this->child('Hidden', 'https://qpos.test/reports/stock-report', true),
            $this->child('', 'https://qpos.test/reports/tax-report'),
        ], 'tour_step8', '<svg id="parent"></svg>');
        $other = new SidebarMenuItem('Products', [
            $this->child('All products', 'https://qpos.test/products'),
        ], 'products');

        $groups = SidebarReports::collect([$other, $reports]);

        $this->assertCount(1, $groups);
        $this->assertCount(1, $groups[0]['items']);
        $this->assertSame('Profit / Loss Report', $groups[0]['items'][0]['title']);
        $this->assertSame('https://qpos.test/reports/profit-loss', $groups[0]['items'][0]['url']);
        $this->assertSame('<svg id="parent"></svg>', $groups[0]['items'][0]['icon']);
        $this->assertNull(SidebarReports::collect([$other]));
    }

    public function test_child_icon_wins_when_the_sidebar_provides_one(): void
    {
        $link = SidebarReports::linkFrom(
            $this->child('Stock Report', '/reports/stock-report', false, '<svg id="child"></svg>'),
            '<svg id="parent"></svg>'
        );

        $this->assertSame('<svg id="child"></svg>', $link['icon']);
    }

    private function child(string $title, string $url, bool $inactive = false, string $icon = ''): object
    {
        return new SidebarMenuChild($title, $url, $inactive, $icon);
    }
}

class SidebarMenuItem
{
    public $title;
    public $icon;
    public $attributes;
    private $children;

    public function __construct(string $title, array $children, string $id, string $icon = '')
    {
        $this->title = $title;
        $this->icon = $icon;
        $this->attributes = ['id' => $id];
        $this->children = $children;
    }

    public function getChilds(): array
    {
        return $this->children;
    }
}

class SidebarMenuChild
{
    public $title;
    public $icon;
    public $attributes;
    private $url;
    private $inactive;

    public function __construct(string $title, string $url, bool $inactive = false, string $icon = '')
    {
        $this->title = $title;
        $this->url = $url;
        $this->icon = $icon;
        $this->inactive = $inactive;
        $this->attributes = ['active' => false];
    }

    public function inactive(): bool
    {
        return $this->inactive;
    }

    public function isDivider(): bool
    {
        return false;
    }

    public function isHeader(): bool
    {
        return false;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
