document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Select all elements with the class 'metascore' or 'detailmetascore'
    // You used 'detailmetascore' in your HTML for the User Score, so we include it.
    const scores = document.querySelectorAll('.metascore, .detailmetascore');

    // 2. Loop through each score element
    scores.forEach(scoreElement => {
        // Get the text content
        const scoreText = scoreElement.innerText;
        
        // Convert to float to handle decimals like "9.2"
        const scoreValue = parseFloat(scoreText);

        // 3. Apply color based on the number
        if (!isNaN(scoreValue)) {
            // For User Scores (usually 0-10), we need to normalize or check range
            // Assuming User Scores > 10 are effectively Metascores (0-100)
            let effectiveScore = scoreValue;
            
            // If score is small (like 9.2), treat it as out of 10 -> multiply by 10 for color logic
            if (scoreValue <= 10) {
                effectiveScore = scoreValue * 10;
            }

            if (effectiveScore >= 90) {
                scoreElement.classList.add('score-dark-green'); // 90-100 (or 9.0-10.0)
            } else if (effectiveScore >= 75) {
                scoreElement.classList.add('score-green');      // 75-89 (or 7.5-8.9)
            } else if (effectiveScore >= 50) {
                scoreElement.classList.add('score-yellow');     // 50-74 (or 5.0-7.4)
            } else {
                scoreElement.classList.add('score-red');        // 0-49 (or 0-4.9)
            }
        } else {
            // If text is '--' or empty, make it gray
            scoreElement.classList.add('score-none');
        }
    });
});