// Wait for the DOM to load before running the script
document.addEventListener("DOMContentLoaded", function() {
    
    // Select all elements with the class 'metascore'
    const scores = document.querySelectorAll('.metascore');

    // Loop through each score element
    scores.forEach(scoreElement => {
        // Get the text content (number) and convert to integer
        const scoreValue = parseInt(scoreElement.innerText);

        // Check if it's a valid number (not '--')
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
            // If no score (e.g., '--'), add a gray color
            scoreElement.classList.add('score-none');
        }
    });
});