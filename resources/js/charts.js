import Chart from 'chart.js/auto';

window.Chart = Chart;

const isDark = () => document.documentElement.classList.contains('dark');

function applyTheme() {
    Chart.defaults.color = isDark() ? '#9ca3af' : '#6b7280';
    Chart.defaults.borderColor = isDark() ? 'rgba(75,85,99,0.4)' : 'rgba(229,231,235,1)';
    Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
}

applyTheme();

window.addEventListener('theme-changed', () => {
    applyTheme();
    Object.values(Chart.instances).forEach((chart) => chart.update());
});

/** Color primario actual (variables CSS del tema). */
window.primaryColor = (shade = 600, alpha = 1) => {
    const rgb = getComputedStyle(document.documentElement).getPropertyValue(`--primary-${shade}`).trim().split(/\s+/).join(',');
    return `rgba(${rgb},${alpha})`;
};
