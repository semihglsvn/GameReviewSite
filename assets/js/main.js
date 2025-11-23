// Wait for the DOM to load before running the script
document.addEventListener("DOMContentLoaded", function() {
    

    const scores = document.querySelectorAll('.metascore');

    scores.forEach(scoreElement => {
        // Get the text content (number) and convert to integer
        const scoreValue = parseInt(scoreElement.innerText);

        // Apply color coding based on score ranges
        if (!isNaN(scoreValue)) {
            if (scoreValue >= 90) {
                scoreElement.classList.add('score-dark-green'); // 90-100
            } else if (scoreValue >= 75) {
                scoreElement.classList.add('score-green');      // 75-89
            } else if (scoreValue >= 50) {
                scoreElement.classList.add('score-yellow');     // 50-74
            } else {
                scoreElement.classList.add('score-red');        // 0-49
            }
        } else {
            scoreElement.classList.add('score-none');
        }
    });
});