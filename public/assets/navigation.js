// Keep normal links usable without JavaScript, or when there is no in-app history.
document.addEventListener('click', (event) => {
    if (document.querySelector('#main')) return;
    const link = event.target.closest('[data-back-button]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    try {
        const previous = new URL(document.referrer);
        if (history.length > 1 && previous.origin === location.origin && previous.href !== location.href && previous.href === link.href) {
            event.preventDefault();
            history.back();
        }
    } catch { /* Direct visits use the parent page in the link. */ }
});
