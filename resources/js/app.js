import './bootstrap';

window.addEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        const search = document.querySelector('[data-global-search]');
        search?.focus();
    }
});
