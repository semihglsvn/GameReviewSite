<?php
// admin/includes/game_modal.php
// Fetch all platforms and genres for the checkboxes
$all_platforms = $conn->query("SELECT id, name FROM platforms ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$all_genres = $conn->query("SELECT id, name FROM genres ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<div id="gameModal" class="modal">
    <div class="modal-content" style="max-width: 700px;"> <span class="close-modal" onclick="closeGameModal()">&times;</span>
        <h3 id="modalTitle" style="margin-top:0;">Add / Edit Game</h3>
        
        <form id="gameForm">
            <input type="hidden" id="modal_game_id" name="game_id" value="">
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Game Title: *</label>
                <input type="text" id="modal_title" name="title" required style="width:100%; padding:8px; box-sizing:border-box;">
            </div>

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Developer:</label>
                    <input type="text" id="modal_developer" name="developer" style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Publisher:</label>
                    <input type="text" id="modal_publisher" name="publisher" style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
            </div>
            
            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Release Date:</label>
                    <input type="date" id="modal_release_date" name="release_date" style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Metascore (0-100):</label>
                    <input type="number" id="modal_metascore" name="metascore" min="0" max="100" style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">ESRB Rating:</label>
                    <select id="modal_esrb" name="esrb_rating" style="width:100%; padding:8px; box-sizing:border-box;">
                        <option value="Not Rated">Not Rated</option>
                        <option value="Everyone">Everyone</option>
                        <option value="Everyone 10+">Everyone 10+</option>
                        <option value="Teen">Teen</option>
                        <option value="Mature">Mature</option>
                        <option value="Adults Only">Adults Only</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Cover Image URL:</label>
                <input type="text" id="modal_cover_image" name="cover_image" style="width:100%; padding:8px; box-sizing:border-box;">
            </div>

            <div style="display:flex; gap:15px; margin-bottom: 15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Platforms:</label>
                    <div style="max-height: 120px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius:4px;">
                        <?php foreach ($all_platforms as $p): ?>
                            <label style="display:block; margin-bottom:4px;">
                                <input type="checkbox" name="platforms[]" value="<?php echo $p['id']; ?>" class="platform-checkbox"> 
                                <?php echo htmlspecialchars($p['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Genres:</label>
                    <div style="max-height: 120px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius:4px;">
                        <?php foreach ($all_genres as $g): ?>
                            <label style="display:block; margin-bottom:4px;">
                                <input type="checkbox" name="genres[]" value="<?php echo $g['id']; ?>" class="genre-checkbox"> 
                                <?php echo htmlspecialchars($g['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Description:</label>
                <textarea id="modal_description" name="description" rows="3" style="width:100%; padding:8px; box-sizing:border-box;"></textarea>
            </div>
            
            <div id="game-feedback" style="margin-bottom:10px; font-weight:bold;"></div>
            
            <div class="modal-actions">
                <button type="button" class="btn-sm" onclick="closeGameModal()" style="background-color:#95a5a6;">Cancel</button>
                <button type="submit" class="btn-sm btn-approve" id="saveGameBtn">Save Game</button>
            </div>
        </form>
    </div>
</div>

<script>
const gameModal = document.getElementById("gameModal");
const gameForm = document.getElementById("gameForm");
const feedback = document.getElementById("game-feedback");

function openGameModal(gameId = null) {
    feedback.innerText = '';
    gameForm.reset(); // Resets text inputs AND unchecks all checkboxes
    document.getElementById("modal_game_id").value = '';
    
    if (gameId) {
        document.getElementById("modalTitle").innerText = "Edit Game";
        document.getElementById("saveGameBtn").innerText = "Update Game";
        
        fetch(`get_game.php?id=${gameId}`)
            .then(response => {
                if (!response.ok) throw new Error("File not found");
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    // Populate text/select fields
                    document.getElementById("modal_game_id").value = data.game.id;
                    document.getElementById("modal_title").value = data.game.title;
                    document.getElementById("modal_developer").value = data.game.developer || '';
                    document.getElementById("modal_publisher").value = data.game.publisher || '';
                    document.getElementById("modal_release_date").value = data.game.release_date || '';
                    document.getElementById("modal_metascore").value = data.game.metascore || '';
                    document.getElementById("modal_esrb").value = data.game.esrb_rating || 'Not Rated';
                    document.getElementById("modal_cover_image").value = data.game.cover_image || '';
                    document.getElementById("modal_description").value = data.game.description || '';

                    // Check the boxes for assigned Platforms
                    data.platforms.forEach(pId => {
                        let checkbox = document.querySelector(`.platform-checkbox[value="${pId}"]`);
                        if (checkbox) checkbox.checked = true;
                    });

                    // Check the boxes for assigned Genres
                    data.genres.forEach(gId => {
                        let checkbox = document.querySelector(`.genre-checkbox[value="${gId}"]`);
                        if (checkbox) checkbox.checked = true;
                    });

                    gameModal.style.display = "block";
                } else {
                    alert("Error loading game data: " + data.error);
                }
            })
            .catch(err => alert("Error: Make sure get_game.php exists! " + err));
    } else {
        document.getElementById("modalTitle").innerText = "Add New Game";
        document.getElementById("saveGameBtn").innerText = "Add Game";
        gameModal.style.display = "block";
    }
}

function closeGameModal() {
    gameModal.style.display = "none";
}

gameForm.addEventListener('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    
    fetch('process_game.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            feedback.style.color = "green";
            feedback.innerText = "Saved successfully! Refreshing...";
            setTimeout(() => { location.reload(); }, 1000);
        } else {
            feedback.style.color = "red";
            feedback.innerText = data.error;
        }
    })
    .catch(err => {
        feedback.style.color = "red";
        feedback.innerText = "Connection error. Check process_game.php.";
    });
});
</script>