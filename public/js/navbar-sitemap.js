// Интерактивная карта сайта для навбара
class NavbarSitemap {
    constructor() {
        this.init();
    }

    init() {
        this.setupNavbarListeners();
        this.createSitemapModal();
    }

    setupNavbarListeners() {
        // Находим все ссылки в навбаре
        const navbarLinks = document.querySelectorAll('ul.navbar > li > a');

        navbarLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.showSitemapForSection(link);
            });
        });
    }

    createSitemapModal() {
        // Создаем модальное окно для карты сайта
        const modal = document.createElement('div');
        modal.id = 'sitemap-modal';
        modal.className = 'sitemap-modal';
        modal.innerHTML = `
            <div class="sitemap-modal-content">
                <div class="sitemap-modal-header">
                    <h3>Карта сайта</h3>
                    <button class="sitemap-modal-close">&times;</button>
                </div>
                <div class="sitemap-modal-body">
                    <div class="sitemap-loading">Загрузка...</div>
                </div>
            </div>
        `;

        // Добавляем стили
        const style = document.createElement('style');
        style.textContent = `
            .sitemap-modal {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                backdrop-filter: blur(5px);
            }

            .sitemap-modal-content {
                background-color: #fff;
                margin: 5% auto;
                padding: 0;
                border-radius: 8px;
                width: 90%;
                max-width: 800px;
                max-height: 80vh;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            }

            .sitemap-modal-header {
                background-color: #0e517e;
                color: white;
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .sitemap-modal-header h3 {
                margin: 0;
                font-size: 18px;
            }

            .sitemap-modal-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: background-color 0.3s;
            }

            .sitemap-modal-close:hover {
                background-color: rgba(255,255,255,0.1);
            }

            .sitemap-modal-body {
                padding: 20px;
                max-height: 60vh;
                overflow-y: auto;
            }

            .sitemap-loading {
                text-align: center;
                padding: 40px;
                color: #666;
                font-size: 16px;
            }

            .sitemap-section-list {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .sitemap-section-item {
                margin-bottom: 10px;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }

            .sitemap-section-link {
                color: #0e517e;
                text-decoration: none;
                font-size: 14px;
                display: block;
                padding: 4px 0;
                transition: color 0.3s;
            }

            .sitemap-section-link:hover {
                color: #08b7be;
                text-decoration: underline;
            }

            .sitemap-full-link {
                display: inline-block;
                margin-top: 15px;
                padding: 8px 16px;
                background-color: #0e517e;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                font-size: 14px;
                transition: background-color 0.3s;
            }

            .sitemap-full-link:hover {
                background-color: #08b7be;
            }

            .sitemap-nested-list {
                list-style: none;
                padding-left: 20px;
                margin: 5px 0 0 0;
            }

            .sitemap-nested-item {
                margin-bottom: 5px;
                padding: 2px 0;
            }

            .sitemap-nested-link {
                color: #2c4f6b;
                text-decoration: none;
                font-size: 13px;
                display: block;
                padding: 2px 0;
                transition: color 0.3s;
            }

            .sitemap-nested-link:hover {
                color: #0e517e;
                text-decoration: underline;
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(modal);

        // Обработчики для модального окна
        const closeBtn = modal.querySelector('.sitemap-modal-close');
        closeBtn.addEventListener('click', () => this.hideSitemap());

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.hideSitemap();
            }
        });

        // Закрытие по ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideSitemap();
            }
        });
    }

    async showSitemapForSection(linkElement) {
        const modal = document.getElementById('sitemap-modal');
        const modalBody = modal.querySelector('.sitemap-modal-body');
        const sectionTitle = linkElement.textContent.trim();

        // Показываем модальное окно
        modal.style.display = 'block';
        modalBody.innerHTML = '<div class="sitemap-loading">Загрузка...</div>';

        try {
            // Получаем ID раздела из data-атрибута или определяем по тексту
            const sectionId = this.getSectionId(linkElement);

            if (sectionId) {
                // Загружаем подразделы через AJAX
                const response = await fetch(`/sitemap/section?section_id=${sectionId}`);
                const html = await response.text();

                // Если есть вложенные страницы, загружаем их рекурсивно
                const nestedHtml = await this.loadNestedSections(sectionId);

                modalBody.innerHTML = `
                    <h4>Раздел: ${sectionTitle}</h4>
                    ${html}
                    ${nestedHtml}
                    <a href="/sitemap" class="sitemap-full-link">Полная карта сайта</a>
                `;
            } else {
                // Если это страница без подразделов, переходим по ссылке
                window.location.href = linkElement.href;
            }
        } catch (error) {
            console.error('Error loading sitemap section:', error);
            modalBody.innerHTML = `
                <div class="sitemap-error">
                    <p>Ошибка загрузки раздела</p>
                    <a href="/sitemap" class="sitemap-full-link">Полная карта сайта</a>
                </div>
            `;
        }
    }

    async loadNestedSections(parentId, level = 1) {
        try {
            const response = await fetch(`/sitemap/section?section_id=${parentId}`);
            const html = await response.text();

            if (!html.trim() || html.includes('sitemap-section-list') && !html.includes('sitemap-section-item')) {
                return '';
            }

            // Парсим HTML чтобы получить ID дочерних страниц
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const links = doc.querySelectorAll('.sitemap-section-link');

            let nestedHtml = '';
            if (level < 3 && links.length > 0) { // Ограничиваем глубину до 3 уровней
                nestedHtml = '<ul class="sitemap-nested-list">';

                for (let link of links) {
                    const href = link.getAttribute('href');
                    const pageId = this.extractPageIdFromUrl(href);

                    if (pageId) {
                        const childNested = await this.loadNestedSections(pageId, level + 1);
                        nestedHtml += `
                            <li class="sitemap-nested-item">
                                <a href="${href}" class="sitemap-nested-link">${link.textContent}</a>
                                ${childNested}
                            </li>
                        `;
                    }
                }

                nestedHtml += '</ul>';
            }

            return nestedHtml;
        } catch (error) {
            console.error('Error loading nested sections:', error);
            return '';
        }
    }

    extractPageIdFromUrl(url) {
        // Извлекаем ID страницы из URL
        // Если URL содержит /page{id}/, извлекаем ID
        const match = url.match(/\/page(\d+)\//);
        return match ? parseInt(match[1]) : null;
    }

    hideSitemap() {
        const modal = document.getElementById('sitemap-modal');
        modal.style.display = 'none';
    }

    getSectionId(linkElement) {
        // Получаем ID раздела из data-атрибута
        return linkElement.dataset.sectionId || null;
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    new NavbarSitemap();
});
