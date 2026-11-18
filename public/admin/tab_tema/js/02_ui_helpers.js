const uiHelpers = {
    bloquear(msg) {
        $.blockUI({
            message: '<h4>' + msg + '</h4>',
            css: {
                border: 'none',
                padding: '15px',
                background: '#000',
                opacity: .6,
                color: '#fff'
            }
        });
    },
    desbloquear() {
        $.unblockUI();
    },
    cssToObject(cssText) {
        if (!cssText) {
            return {};
        }
        const obj = {};
        cssText.split(';').forEach(row => {
            const parts = row.split(':');
            if (parts.length < 2) {
                return;
            }
            const key = parts[0].trim().replace(/-([a-z])/g, (_, c) => c.toUpperCase());
            const value = parts.slice(1).join(':').trim();
            if (key) {
                obj[key] = value;
            }
        });
        return obj;
    },
    miniaturaPreview(url) {
        if (!url || url.trim() === '') {
            return '—';
        }
        return `
            <div style="width:55px; height:55px; border-radius:10px; border:1px solid #dcdcdc; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#f9f9f9; cursor:pointer;" class="ver-imagen-fullscreen" data-url="${url}">
                <img src="${url}" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null;this.src='https://barsi-img.b-cdn.net/recursos/7pyi.jpg';"/>
            </div>
        `;
    }
};