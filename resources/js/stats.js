import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

// Colores de 04 §9; la tabla de datos debajo del canvas sigue visible si este script no carga.
const css = getComputedStyle(document.documentElement);
const t = (name) => css.getPropertyValue(name).trim();
const data = JSON.parse(document.getElementById('stats-data').textContent);

Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 13;
Chart.defaults.color = t('--chart-text');

new Chart(document.getElementById('chart-genres'), {
  type: 'bar',
  data: {
    labels: data.labels,
    datasets: [{
      data: data.values,
      backgroundColor: t('--chart-bar'),
      hoverBackgroundColor: t('--chart-bar-hover'),
      hoverBorderColor: t('--chart-text'),
      hoverBorderWidth: 1,
      borderRadius: 0,
      maxBarThickness: 40,
    }],
  },
  options: {
    indexAxis: 'y', // géneros en horizontal: etiquetas largas legibles
    responsive: true,
    maintainAspectRatio: false,
    animation: matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 140 },
    plugins: {
      legend: { display: false }, // una sola serie
      tooltip: { backgroundColor: t('--chart-tooltip'), padding: 10, cornerRadius: 4, displayColors: false },
    },
    scales: {
      x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: t('--chart-grid') }, title: { display: true, text: 'Álbumes' } },
      y: { grid: { display: false } },
    },
  },
});
