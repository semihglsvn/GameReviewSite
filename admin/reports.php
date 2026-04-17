<?php
// admin/reports.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

// Fetch all PENDING reports
$query = "
    SELECT r.id as report_id, r.reasons, r.created_at as report_date,
           u_reporter.id as reporter_id, u_reporter.username as reporter_name, u_reporter.false_report_strikes,
           rev.id as review_id, rev.comment, rev.score,
           u_author.id as author_id, u_author.username as author_name
    FROM reports r
    JOIN users u_reporter ON r.reporter_id = u_reporter.id
    JOIN reviews rev ON r.review_id = rev.id
    JOIN users u_author ON rev.user_id = u_author.id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
";
$reports_result = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

include 'includes/admin_header.php'; 
include 'includes/admin_sidebar.php'; 
?>

<main class="admin-content">
    <div class="admin-header">
        <h2>Reported Reviews (<?php echo count($reports_result); ?> Pending)</h2>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes(this)"></th>
                    <th>Reporter (Strikes)</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Offending User</th>
                    <th style="width: 30%;">Review Snippet</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports_result)): ?>
                    <tr><td colspan="7" style="text-align: center;">No pending reports. Great job!</td></tr>
                <?php else: ?>
                    <?php foreach ($reports_result as $report): ?>
                        <tr id="report-row-<?php echo $report['report_id']; ?>">
                            <td style="text-align: center;">
                                <input type="checkbox" class="report-checkbox" value="<?php echo $report['report_id']; ?>">
                            </td>
                            <td>
                                <strong><a href="../profile.php?id=<?php echo $report['reporter_id']; ?>" target="_blank" style="color:#2c3e50; text-decoration:none;"><?php echo htmlspecialchars($report['reporter_name']); ?> &#8599;</a></strong><br>
                                <span style="font-size:12px; color: <?php echo $report['false_report_strikes'] >= 7 ? 'red' : 'gray'; ?>;">
                                    <?php echo $report['false_report_strikes']; ?>/10 Strikes
                                </span>
                            </td>
                            <td><span style="color:#e74c3c; font-weight:bold;"><?php echo htmlspecialchars($report['reasons']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                            <td>
                                <strong><a href="../profile.php?id=<?php echo $report['author_id']; ?>" target="_blank" style="color:#2c3e50; text-decoration:none;"><?php echo htmlspecialchars($report['author_name']); ?> &#8599;</a></strong>
                            </td>
                            <td>
                                <div style="font-size:12px; font-weight:bold; margin-bottom:3px;">Score: <?php echo $report['score']; ?>/10</div>
                                <div style="font-size:13px; color:#555; background:#f9f9f9; padding:8px; border-radius:4px; max-height: 45px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    "<?php echo htmlspecialchars($report['comment']); ?>"
                                </div>
                                <button class="btn-sm" style="background:transparent; color:#3498db; padding:0; margin-top:5px; border:none; text-decoration:underline;" onclick="openReviewModal('<?php echo addslashes(htmlspecialchars($report['author_name'])); ?>', '<?php echo addslashes(htmlspecialchars($report['score'])); ?>', '<?php echo addslashes(htmlspecialchars(str_replace(["\r", "\n"], " ", $report['comment']))); ?>')">Read Full Review</button>
                            </td>
                            <td>
                                <button class="btn-sm btn-approve" onclick="processReport(<?php echo $report['report_id']; ?>, 'dismiss')">Dismiss</button>
                                <button class="btn-sm btn-delete" onclick="openBanModal(<?php echo $report['report_id']; ?>, <?php echo $report['author_id']; ?>, '<?php echo addslashes(htmlspecialchars($report['author_name'])); ?>')">Ban User</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if (!empty($reports_result)): ?>
        <div style="margin-top: 15px; padding: 10px; background: #ecf0f1; border-radius: 4px; display: flex; align-items: center; gap: 15px;">
            <span style="font-weight: bold;">Mass Action:</span>
            <button class="btn-sm btn-approve" onclick="massDismiss()">Dismiss Selected (Give Strikes)</button>
            <span style="font-size: 12px; color: #7f8c8d;">Bans must be done individually to set duration.</span>
        </div>
        <?php endif; ?>
    </div>
</main>

<div id="banModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeBanModal()">&times;</span>
        <h3 style="margin-top:0; color:#e74c3c;">Punish User</h3>
        <p>Select ban duration for <strong id="banUsername">User</strong>:</p>
        
        <form id="banForm">
            <input type="hidden" id="modal_report_id">
            <input type="hidden" id="modal_author_id">
            
            <div style="margin-bottom: 10px;">
                <label><input type="radio" name="duration" value="24h" checked> 24 Hours</label>
            </div>
            <div style="margin-bottom: 10px;">
                <label><input type="radio" name="duration" value="7d"> 7 Days</label>
            </div>
            <div style="margin-bottom: 15px;">
                <label><input type="radio" name="duration" value="perm"> <span style="color:red; font-weight:bold;">Permanent Ban</span></label>
            </div>
            
            <div id="ban-feedback" style="margin-bottom:10px; font-weight:bold;"></div>
            
            <div class="modal-actions">
                <button type="button" class="btn-sm" onclick="closeBanModal()" style="background-color:#95a5a6;">Cancel</button>
                <button type="button" class="btn-sm btn-delete" onclick="submitBan()">Execute Ban</button>
            </div>
        </form>
    </div>
</div>

<div id="fullReviewModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeReviewModal()">&times;</span>
        <h3 style="margin-top:0;">Review by <span id="fullReviewAuthor" style="color:#2c3e50;"></span></h3>
        <div style="font-weight:bold; margin-bottom: 10px;">Score: <span id="fullReviewScore"></span>/10</div>
        <p id="fullReviewText" style="background:#f9f9f9; padding:15px; border-radius:4px; border:1px solid #eee; word-wrap:break-word; white-space:pre-wrap;"></p>
    </div>
</div>

<script>
// Checkbox Logic
function toggleAllCheckboxes(masterCheckbox) {
    let checkboxes = document.querySelectorAll('.report-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
}

// Single Report Logic
function processReport(reportId, action, duration = null, authorId = null) {
    let formData = new FormData();
    formData.append('report_id', reportId);
    formData.append('action', action);
    if (duration) formData.append('duration', duration);
    if (authorId) formData.append('author_id', authorId);

    fetch('process_report_action.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('report-row-' + reportId).remove();
            closeBanModal();
        } else {
            alert("Error: " + data.error);
        }
    });
}

// Mass Dismiss Logic
function massDismiss() {
    // Gather all checked checkbox values
    let selectedIds = Array.from(document.querySelectorAll('.report-checkbox:checked')).map(cb => cb.value);
    
    if (selectedIds.length === 0) {
        alert("Please select at least one report to dismiss.");
        return;
    }
    
    if (!confirm(`Are you sure you want to dismiss these ${selectedIds.length} reports? The reporters will receive strikes.`)) {
        return;
    }

    let formData = new FormData();
    formData.append('action', 'mass_dismiss');
    // Send array of IDs as a JSON string
    formData.append('report_ids', JSON.stringify(selectedIds));

    fetch('process_report_action.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Remove all selected rows from the UI
            selectedIds.forEach(id => document.getElementById('report-row-' + id).remove());
            document.getElementById('selectAll').checked = false; // Reset master checkbox
        } else {
            alert("Error: " + data.error);
        }
    });
}

// View Full Review Modal
function openReviewModal(author, score, text) {
    document.getElementById("fullReviewAuthor").innerText = author;
    document.getElementById("fullReviewScore").innerText = score;
    document.getElementById("fullReviewText").innerText = text;
    document.getElementById("fullReviewModal").style.display = "block";
}
function closeReviewModal() { document.getElementById("fullReviewModal").style.display = "none"; }

// Ban Modal
const banModal = document.getElementById("banModal");
function openBanModal(reportId, authorId, authorName) {
    document.getElementById("modal_report_id").value = reportId;
    document.getElementById("modal_author_id").value = authorId;
    document.getElementById("banUsername").innerText = authorName;
    document.getElementById("ban-feedback").innerText = "";
    banModal.style.display = "block";
}
function closeBanModal() { banModal.style.display = "none"; }
function submitBan() {
    let reportId = document.getElementById("modal_report_id").value;
    let authorId = document.getElementById("modal_author_id").value;
    let duration = document.querySelector('input[name="duration"]:checked').value;
    processReport(reportId, 'ban', duration, authorId);
}
</script>

<?php include 'includes/footer.php'; ?>