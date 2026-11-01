const uiHelpersMethods = {
  bloquear(msg) {
    $.blockUI({
      message: `<h4>${msg}</h4>`,
      css: { border:'none', padding:'15px', background:'#000', opacity:.6, color:'#fff' }
    });
  },
  cssToObject(css) {
    if (!css) return {};
    const obj = {};
    css.split(';').forEach(rule => {
      const parts = rule.split(':');
      if (parts.length < 2) return;
      const key = parts[0].trim().replace(/-([a-z])/g, g => g[1].toUpperCase());
      const value = parts.slice(1).join(':').trim();
      if (key && value) {
        obj[key] = value;
      }
    });
    return obj;
  }
};