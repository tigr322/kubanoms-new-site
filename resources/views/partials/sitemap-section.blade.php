<ul class="sitemap-ajax-list">
    @foreach($pages as $page)
        <li class="sitemap-ajax-item">
            <a href="{{ $page->url ?: '#' }}" class="sitemap-ajax-link" data-section-id="{{ $page->id }}">
                {{ $page->title_short ?: $page->title }}
            </a>
        </li>
    @endforeach
</ul>

<style>
.sitemap-ajax-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sitemap-ajax-item {
    margin-bottom: 8px;
    padding: 3px 0;
}

.sitemap-ajax-link {
    color: #2c4f6b;
    text-decoration: none;
    font-size: 14px;
    display: block;
    padding: 3px 0;
}

.sitemap-ajax-link:hover {
    color: #0e517e;
    text-decoration: underline;
}
</style>
