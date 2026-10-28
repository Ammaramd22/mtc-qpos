{{-- Touch POS: left category rail --}}
<aside class="vspos-touch-cats" id="vspos_touch_cats" aria-label="@lang('category.category')">
    <div class="vspos-touch-cats-head">
        <span>@lang('category.category')</span>
    </div>
    <div class="vspos-touch-cats-scroll" id="vspos_touch_cats_scroll">
        <button type="button"
            class="vspos-touch-cat is-active"
            data-value="all"
            data-name="@lang('lang_v1.all_category')">
            <span class="vspos-touch-cat-label">@lang('lang_v1.all_category')</span>
        </button>

        @if (!empty($categories))
            @foreach ($categories as $category)
                <button type="button"
                    class="vspos-touch-cat"
                    data-value="{{ $category['id'] }}"
                    data-name="{{ $category['name'] }}"
                    data-has-sub="{{ !empty($category['sub_categories']) ? 1 : 0 }}">
                    <span class="vspos-touch-cat-label">{{ $category['name'] }}</span>
                    @if (!empty($category['sub_categories']))
                        <span class="vspos-touch-cat-dot" aria-hidden="true"></span>
                    @endif
                </button>

                @if (!empty($category['sub_categories']))
                    <div class="vspos-touch-subcats" data-category-id="{{ $category['id'] }}" hidden>
                        @foreach ($category['sub_categories'] as $sc)
                            @if ($sc['parent_id'] != 0)
                                <button type="button"
                                    class="vspos-touch-cat vspos-touch-cat--sub"
                                    data-value="{{ $sc['id'] }}"
                                    data-name="{{ $sc['name'] }}"
                                    data-parent="{{ $category['id'] }}">
                                    <span class="vspos-touch-cat-label">{{ $sc['name'] }}</span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</aside>
