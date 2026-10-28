<?php

namespace App\Support;

/**
 * Reads the Reports dropdown already built for the sidebar.
 * Names, URLs, icons, and visibility stay on that menu.
 */
class SidebarReports
{
    public const MENU_ID = 'tour_step8';

    /**
     * @param  iterable<int, object>  $items  Root items from the admin sidebar menu.
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>|null
     *         Null when the Reports dropdown is not in the menu.
     */
    public static function collect(iterable $items): ?array
    {
        foreach ($items as $item) {
            $attributes = (isset($item->attributes) && is_array($item->attributes)) ? $item->attributes : [];
            if (($attributes['id'] ?? null) !== self::MENU_ID) {
                continue;
            }

            $fallbackIcon = (! empty($item->icon) && is_string($item->icon)) ? $item->icon : '';
            $children = method_exists($item, 'getChilds') ? $item->getChilds() : [];
            $links = [];
            foreach ($children as $child) {
                $link = self::linkFrom($child, $fallbackIcon);
                if ($link !== null) {
                    $links[] = $link;
                }
            }

            return self::groupLinks($links);
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $links
     * @return array<int, array{key: string, label: string, items: array<int, array<string, mixed>>}>
     */
    public static function groupLinks(array $links): array
    {
        $groups = [];
        $order = [];

        foreach ($links as $link) {
            $key = self::sectionFor((string) ($link['url'] ?? ''));
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'label' => self::sectionLabel($key),
                    'items' => [],
                ];
                $order[] = $key;
            }
            $groups[$key]['items'][] = $link;
        }

        $grouped = [];
        foreach ($order as $key) {
            $grouped[] = $groups[$key];
        }

        return $grouped;
    }

    public static function sectionFor(string $url): string
    {
        $path = strtolower((string) (parse_url($url, PHP_URL_PATH) ?? $url));
        $rules = [
            'customers' => '/customer|loyalty|contact/',
            'operations' => '/sales-representative|service-staff|cheque|expense|register|table-report|activity-log|nutmeg/',
            'stock' => '/stock|trending|lot-report|expiry|items-report/',
            'sales' => '/profit|purchase|sell|sale|payment|gst|tax/',
        ];

        foreach ($rules as $key => $pattern) {
            if (preg_match($pattern, $path)) {
                return $key;
            }
        }

        return 'other';
    }

    public static function sectionLabel(string $key): string
    {
        $english = [
            'sales' => 'Sales & purchases',
            'stock' => 'Stock',
            'customers' => 'Customers',
            'operations' => 'Operations',
            'other' => 'More reports',
        ];
        $fallback = $english[$key] ?? $english['other'];
        $langKey = 'report.reports_section_'.$key;

        if (! function_exists('app') || ! app()->bound('translator')) {
            return $fallback;
        }

        $translated = __($langKey);

        return $translated === $langKey ? $fallback : $translated;
    }

    /**
     * @return array{title: string, url: string, icon: string, active: bool}|null
     */
    public static function linkFrom(object $child, string $fallbackIcon = ''): ?array
    {
        if (method_exists($child, 'inactive') && $child->inactive()) {
            return null;
        }
        if (method_exists($child, 'isDivider') && $child->isDivider()) {
            return null;
        }
        if (method_exists($child, 'isHeader') && $child->isHeader()) {
            return null;
        }

        $url = method_exists($child, 'getUrl') ? trim((string) $child->getUrl()) : '';
        if ($url === '' || str_ends_with($url, '/#')) {
            return null;
        }

        $title = trim((string) ($child->title ?? ''));
        if ($title === '') {
            return null;
        }

        $icon = (! empty($child->icon) && is_string($child->icon)) ? $child->icon : $fallbackIcon;
        $active = false;
        if (isset($child->attributes) && is_array($child->attributes) && array_key_exists('active', $child->attributes)) {
            $active = (bool) $child->attributes['active'];
        }

        return [
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'active' => $active,
        ];
    }
}
