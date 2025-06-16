const weatherContainer = document.getElementById('current-weather');
const form = document.getElementById('weather-form');

// Fetch current weather from OpenWeatherMap API
async function fetchWeather() {
    const apiKey = 'YOUR_API_KEY'; // Replace with your OpenWeatherMap API key
    const city = 'Rennes,FR';
    const url = `https://api.openweathermap.org/data/2.5/weather?q=${city}&units=metric&lang=fr&appid=${apiKey}`;
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des données météo');
        }
        const data = await response.json();
        const desc = data.weather[0].description;
        const temp = data.main.temp;
        weatherContainer.innerHTML = `<p>Météo actuelle : <strong>${desc}</strong>, température : <strong>${temp}°C</strong></p>`;
    } catch (err) {
        weatherContainer.innerHTML = `<p>Impossible de récupérer la météo : ${err.message}</p>`;
    }
}

form.addEventListener('submit', (e) => {
    e.preventDefault();
    const desired = document.getElementById('desired-weather').value;
    alert(`Vous avez demandé une météo '${desired}', mais il est impossible de contrôler la météo réelle.`);
});

fetchWeather();
