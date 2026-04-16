<div id="report-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <span class="close-report-btn modal-close-right">&times;</span>
        <h3 class="mt-0">Report Review</h3>
        <p class="text-sub-grey mb-15" style="font-size:14px;">Select all that apply:</p>
        
        <form id="reportForm">
            <input type="hidden" id="report_review_id" name="review_id" value="">
            <div class="text-left mb-20">
                <label class="modal-label-block"><input type="checkbox" name="reasons[]" value="spam" class="modal-checkbox">Spam or Advertising</label>
                <label class="modal-label-block"><input type="checkbox" name="reasons[]" value="abuse" class="modal-checkbox">Abusive or Harassing</label>
                <label class="modal-label-block"><input type="checkbox" name="reasons[]" value="irrelevant" class="modal-checkbox">Off-topic / Irrelevant</label>
                <label class="modal-label-block"><input type="checkbox" name="reasons[]" value="spoiler" class="modal-checkbox">Contains Spoilers</label>
            </div>
            <div id="report-feedback" style="margin-bottom: 10px; font-weight: bold;"></div>
            <div class="text-right">
                <button type="button" id="cancel-report-btn" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-submit modal-submit-btn">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const reportModal = document.getElementById('report-modal');
    const reportBtns = document.querySelectorAll('.btn-report');
    const reviewIdInput = document.getElementById('report_review_id');
    const feedback = document.getElementById('report-feedback');

    // Open Modal and set the Review ID
    reportBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            reviewIdInput.value = btn.getAttribute('data-review-id');
            feedback.innerHTML = ''; // clear old messages
            reportModal.style.display = 'flex';
        });
    });

    // Close Modal Logic (Assuming you have close buttons hooked up)
    document.getElementById('cancel-report-btn').addEventListener('click', () => { reportModal.style.display = 'none'; });
    document.querySelector('.close-report-btn').addEventListener('click', () => { reportModal.style.display = 'none'; });

    // Handle AJAX Submission
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        fetch('process_report.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                feedback.style.color = "green";
                feedback.innerText = "Report submitted successfully. Thank you.";
                setTimeout(() => { reportModal.style.display = 'none'; }, 1500);
            } else {
                feedback.style.color = "red";
                feedback.innerText = data.error;
            }
        })
        .catch(err => {
            feedback.style.color = "red";
            feedback.innerText = "An error occurred. Please try again.";
        });
    });
});
</script>